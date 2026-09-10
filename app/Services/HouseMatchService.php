<?php

namespace App\Services;

use App\Models\House;
use App\Support\Geo;
use Illuminate\Support\Collection;

/**
 * Deterministic counterpart to HouseSearchAiService: turns a filter set into a
 * real database query and a small set of "facts" describing what happened, so
 * the reply-composing LLM call only ever narrates real numbers - it never sees
 * raw rows or gets to invent a count/listing itself.
 */
class HouseMatchService
{
    public function __construct(protected LandmarkGeocoder $landmarkGeocoder)
    {
    }

    // Show all matches as cards up to this many; beyond it, ask a narrowing
    // question instead of dumping the full list.
    protected const NARROW_THRESHOLD = 6;

    // How many cards to show as a "taste" when asking the user to narrow down.
    protected const PREVIEW_COUNT = 3;

    protected const MAX_CARDS = 6;

    // A "nearby" preference (e.g. bus_stage) counts as satisfied within this
    // many minutes, since nearby_places only stores a minutes estimate per
    // category, not coordinates for a real radius search.
    protected const NEARBY_MINUTES_THRESHOLD = 20;

    public function search(array $filters): array
    {
        $unconfirmed = $filters['unconfirmed_preferences'] ?? [];
        $propertyName = trim($filters['property_name'] ?? '');

        // Distinguish "that property doesn't exist" from "that property exists
        // but nothing matches the other filters" - conflating them previously
        // meant a query for a nonexistent property silently fell through to
        // whatever filters were carried over from an earlier turn instead of
        // ever being recognized as a property-name search at all.
        if ($propertyName !== '' && ! \App\Models\Location::where('location_name', 'like', "%{$propertyName}%")->exists()) {
            return [
                'results' => collect(),
                'branch' => 'property_not_found',
                'facts' => [
                    'branch' => 'property_not_found',
                    'requested_property_name' => $propertyName,
                    'filters' => $this->publicFilters($filters),
                ],
            ];
        }

        $matches = $this->baseQuery($filters)->get();

        // A "near <landmark>" or "use my location" request re-orders whatever
        // the exact-filter query already found (never removes anything, never
        // widens the query) - a listing with no resolvable point just keeps
        // its original position at the end, rather than being dropped for
        // lack of a pin.
        $distancePoint = $this->resolveDistancePoint($filters);
        if ($distancePoint) {
            $matches = $this->sortByDistanceFrom($matches, $distancePoint);
        }

        $count = $matches->count();

        if ($count === 0 && filled($filters['area'] ?? null)) {
            return $this->zeroResultsInArea($filters, $unconfirmed);
        }

        if ($count === 0) {
            return [
                'results' => collect(),
                'branch' => 'none',
                'facts' => array_merge([
                    'branch' => 'none',
                    'filters' => $this->publicFilters($filters),
                ], $this->noMatchSuggestions($filters)),
            ];
        }

        if ($count > self::NARROW_THRESHOLD) {
            $sample = $matches->take(self::PREVIEW_COUNT);
            $prices = $matches->map(fn (House $h) => $this->priceFor($h))->filter();

            return [
                'results' => $sample,
                'branch' => 'narrow',
                'facts' => [
                    'branch' => 'narrow',
                    'count' => $count,
                    'shown_count' => $sample->count(),
                    'filters' => $this->publicFilters($filters),
                    'price_range' => $prices->isNotEmpty()
                        ? ['min' => (int) $prices->min(), 'max' => (int) $prices->max()]
                        : null,
                    'house_types_available' => $matches->pluck('house_type')->unique()->values()->all(),
                    'sample' => $this->summarize($sample),
                    'unconfirmed_preferences' => $unconfirmed,
                ],
            ];
        }

        $cards = $matches->take(self::MAX_CARDS);

        return [
            'results' => $cards,
            'branch' => 'results',
            'facts' => [
                'branch' => 'results',
                'count' => $count,
                'filters' => $this->publicFilters($filters),
                'sample' => $this->summarize($cards),
                'unconfirmed_preferences' => $unconfirmed,
            ],
        ];
    }

    protected function zeroResultsInArea(array $filters, array $unconfirmed): array
    {
        $requestedArea = $filters['area'];

        // The user already agreed to see other areas - re-run without the
        // area constraint and show whatever else matches.
        if (($filters['area_flexible'] ?? null) === true) {
            $broader = (clone $this->baseQuery(array_merge($filters, ['area' => null])))->get();

            if ($broader->isEmpty()) {
                return [
                    'results' => collect(),
                    'branch' => 'none',
                    'facts' => [
                        'branch' => 'none',
                        'filters' => $this->publicFilters($filters),
                    ],
                ];
            }

            $cards = $broader->take(self::MAX_CARDS);

            return [
                'results' => $cards,
                'branch' => 'alternatives_shown',
                'facts' => [
                    'branch' => 'alternatives_shown',
                    'count' => $broader->count(),
                    'requested_area' => $requestedArea,
                    'filters' => $this->publicFilters($filters),
                    'sample' => $this->summarize($cards),
                    'unconfirmed_preferences' => $unconfirmed,
                ],
            ];
        }

        // Otherwise, don't show anything yet - surface counts for other areas
        // that still match every other filter (type, budget, amenities...),
        // so the composer isn't quoting counts for the wrong kind of unit.
        $elsewhere = (clone $this->baseQuery(array_merge($filters, ['area' => null])))->get();

        $alternatives = $elsewhere
            ->groupBy(fn (House $h) => $h->location?->area?->name ?? $h->location?->geo_id)
            ->filter(fn ($group, $area) => $area && $area !== $requestedArea)
            ->map->count()
            ->sortDesc()
            ->take(4)
            ->map(fn ($count, $area) => ['area' => $area, 'count' => $count])
            ->values()
            ->all();

        return [
            'results' => collect(),
            'branch' => 'zero_results',
            'facts' => [
                'branch' => 'zero_results',
                'filters' => $this->publicFilters($filters),
                'alternative_areas' => $alternatives,
                'unconfirmed_preferences' => $unconfirmed,
            ],
        ];
    }

    /**
     * When nothing matches at all (no area was even specified, so there's no
     * "other areas" fallback), relax one filter at a time against the real
     * data so the composer has actual numbers to offer instead of a generic
     * "try again" - e.g. the true cheapest price for that unit type, or which
     * unit types genuinely exist under the given budget.
     */
    protected function noMatchSuggestions(array $filters): array
    {
        $facts = [];

        // The property itself exists (property_not_found already short-circuited
        // above otherwise) but nothing there matches the other filters -
        // suggesting the cheapest match ANYWHERE else would point at a
        // completely different building than the one actually asked about.
        if (filled($filters['property_name'] ?? null)) {
            return $facts;
        }

        if (filled($filters['max_rent'] ?? null) && filled($filters['house_type'] ?? null)) {
            $withoutBudget = (clone $this->baseQuery(array_merge($filters, ['max_rent' => null])))->get();
            $prices = $withoutBudget->map(fn (House $h) => $this->priceFor($h))->filter();

            if ($prices->isNotEmpty()) {
                $facts['cheapest_available_for_type'] = (int) $prices->min();
            }
        }

        if (filled($filters['house_type'] ?? null)) {
            $withoutType = (clone $this->baseQuery(array_merge($filters, ['house_type' => null])))->get();

            if ($withoutType->isNotEmpty()) {
                $facts['available_house_types'] = $withoutType->pluck('house_type')->unique()->values()->all();
            }
        }

        return $facts;
    }

    /**
     * A real GPS point from "use my location" is preferred whenever both are
     * present - it's the more precise version of the same "rank by distance
     * from a point" request a landmark mention makes.
     */
    protected function resolveDistancePoint(array $filters): ?array
    {
        if (filled($filters['near_lat'] ?? null) && filled($filters['near_lng'] ?? null)) {
            return ['lat' => (float) $filters['near_lat'], 'lng' => (float) $filters['near_lng']];
        }

        $landmark = trim($filters['landmark'] ?? '');

        return $landmark !== '' ? $this->landmarkGeocoder->geocode($landmark) : null;
    }

    /** Closest-first, by real distance to $point - houses with no resolvable map point (see Location::mapPoint()) are appended, unranked, in their original order rather than dropped. */
    protected function sortByDistanceFrom(Collection $houses, array $point): Collection
    {
        $withDistance = $houses->map(function (House $house) use ($point) {
            $housePoint = $house->location?->mapPoint();

            $house->distance_km = $housePoint
                ? Geo::distanceKm($point['lat'], $point['lng'], $housePoint['lat'], $housePoint['lng'])
                : null;

            return $house;
        });

        [$ranked, $unranked] = $withDistance->partition(fn (House $h) => $h->distance_km !== null);

        return $ranked->sortBy('distance_km')->values()->concat($unranked);
    }

    protected function baseQuery(array $filters)
    {
        $mode = $filters['listing_mode'] ?? 'long_term';

        $query = $mode === 'short_term' ? House::bnbVisible() : House::publiclyVisible();
        $query->with(['location.area', 'photos', 'pricePackages']);

        if (filled($filters['area'] ?? null)) {
            $query->inAreaOrCity($filters['area']);
        }

        if (filled($filters['property_name'] ?? null)) {
            $propertyName = $filters['property_name'];
            $query->whereHas('location', fn ($q) => $q->where('location_name', 'like', "%{$propertyName}%"));
        }

        if (filled($filters['house_type'] ?? null) && in_array($filters['house_type'], House::UNIT_TYPES, true)) {
            $query->where('house_type', $filters['house_type']);
        }

        if (filled($filters['max_rent'] ?? null)) {
            $max = (int) $filters['max_rent'];

            if ($mode === 'short_term') {
                $query->whereHas('pricePackages', fn ($q) => $q->where('price', '<=', $max));
            } else {
                $query->where('rent_amount', '<=', $max);
            }
        }

        foreach (($filters['amenities'] ?? []) as $amenity) {
            if (in_array($amenity, House::AMENITIES, true)) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        foreach (($filters['nearby'] ?? []) as $slug) {
            if (array_key_exists($slug, House::NEARBY_CATEGORIES)) {
                $query->where("nearby_places->{$slug}", '<=', self::NEARBY_MINUTES_THRESHOLD);
            }
        }

        return $query->latest();
    }

    protected function summarize(Collection $houses): array
    {
        return $houses->map(function (House $h) {
            $summary = [
                'type' => $h->house_type,
                'area' => $h->location?->geo_id,
                'price' => $this->priceFor($h),
            ];

            // Only present when sortByDistanceFrom() actually ranked this house
            // (a landmark search matched and this listing had a resolvable
            // point) - lets the composer cite a real "X km away" instead of
            // guessing whether the top result is actually close.
            if (isset($h->distance_km)) {
                $summary['distance_km'] = round($h->distance_km, 1);
            }

            return $summary;
        })->all();
    }

    protected function priceFor(House $house): ?int
    {
        if ($house->isShortTerm()) {
            $cheapest = $house->pricePackages->sortBy('price')->first();

            return $cheapest ? (int) $cheapest->price : null;
        }

        return $house->rent_amount ? (int) $house->rent_amount : null;
    }

    protected function publicFilters(array $filters): array
    {
        $public = array_intersect_key($filters, array_flip([
            'area', 'listing_mode', 'house_type', 'max_rent', 'amenities', 'nearby', 'landmark', 'property_name',
        ]));

        // A flag, never the raw coordinates - the visitor's exact GPS position
        // has no business leaving this server, let alone going to a
        // third-party LLM API just to phrase a reply.
        if (filled($filters['near_lat'] ?? null) && filled($filters['near_lng'] ?? null)) {
            $public['near_me'] = true;
        }

        return $public;
    }

    /**
     * Card data for the chat UI - a plain array snapshot (not the Eloquent
     * models themselves) so it can be stored directly on a chat message and
     * survive Livewire's state serialization across turns unchanged.
     */
    public function toCards(Collection $houses): array
    {
        return $houses->map(function (House $house) {
            $photo = $house->photos->first();
            $isStay = $house->isShortTerm();
            $price = $this->priceFor($house);

            return [
                'id' => $house->id,
                'title' => $house->publicName(),
                'type' => $house->house_type,
                'area' => $house->location?->geo_id,
                'price' => $price,
                'price_unit' => $isStay ? ($house->pricePackages->sortBy('price')->first()->billing_unit ?? 'night') : 'mo',
                'image' => $photo?->url(),
                'url' => $isStay ? route('stays.show', $house) : route('listings.show', $house),
            ];
        })->all();
    }
}
