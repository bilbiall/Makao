<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Support\Geo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-time (or occasional) backfill of Area.latitude/longitude via Komoot's free
 * Photon geocoder (OpenStreetMap data) - not a live/interactive geocoder, so this
 * is a batch command, not something called from a request. Only ever fills in
 * missing coordinates; never overwrites an area that already has them unless
 * --force is passed. An area that fails to geocode is skipped (logged), not
 * fatal - it just stays out of "nearby areas" suggestions until retried, same as
 * before this feature existed.
 *
 * Photon, not Nominatim: Nominatim's public instance 403s this app's requests
 * outright (an IP-level block per its usage policy, unrelated to rate or
 * User-Agent - confirmed by hand with curl), so Photon is the working free
 * alternative, not a preference.
 *
 * Two safeguards against a wrong-but-plausible-looking match - both learned the
 * hard way from a real run that silently matched "Muthaiga, Nanyuki" to
 * Nairobi's Muthaiga and "Milimani" in four different towns all to Nakuru's:
 *  1. Only an EXACT (case-insensitive) name match is ever accepted - a query
 *     that returns no candidate actually named what we searched for is a "no
 *     match", never a same-name-different-place guess.
 *  2. The accepted match must be within MAX_DISTANCE_FROM_CITY_KM of that
 *     area's own city (geocoded once per city as a reference point) - catches
 *     a same-named place that exists somewhere else in Kenya entirely.
 */
class GeocodeAreas extends Command
{
    protected $signature = 'areas:geocode {--force : Re-geocode areas that already have coordinates} {--city= : Only this city (by name) - combine with --force to re-check just one city\'s areas without touching others}';

    protected $description = 'Backfill Area.latitude/longitude from OpenStreetMap data (via Photon)';

    private const MAX_DISTANCE_FROM_CITY_KM = 40.0;

    /** @var array<string, array{lat: float, lng: float}|null> */
    private array $cityReferencePoints = [];

    public function handle(): int
    {
        $areas = Area::with('city')
            ->when(! $this->option('force'), fn ($q) => $q->whereNull('latitude'))
            ->when($this->option('city'), fn ($q, $city) => $q->whereHas('city', fn ($q) => $q->where('name', $city)))
            ->get();

        if ($areas->isEmpty()) {
            $this->info('Nothing to geocode.');

            return self::SUCCESS;
        }

        $geocoded = 0;
        $failed = 0;
        $requestCount = 0;

        foreach ($areas->values() as $area) {
            $cityName = $area->city->name ?? null;
            $cityPoint = $cityName ? $this->cityReferencePoint($cityName, $requestCount) : null;

            $query = trim($area->name.', '.($cityName ?? '').', Kenya');
            $match = $this->geocode($query, $area->name, $cityPoint, $requestCount);

            if ($match) {
                $area->update(['latitude' => $match['lat'], 'longitude' => $match['lng']]);
                $geocoded++;
                $this->line("  {$query} -> {$match['lat']}, {$match['lng']}");
            } else {
                $failed++;
                // --force means "re-check", not "keep the old value if the fresh
                // check fails" - a stale, no-longer-trusted match (e.g. from before
                // the exact-name/distance safeguards existed) must be cleared, not
                // left in place looking validated when it silently isn't.
                if ($this->option('force') && $area->latitude !== null) {
                    $area->update(['latitude' => null, 'longitude' => null]);
                }
                $this->warn("  {$query} -> no match");
            }
        }

        $this->info("Geocoded {$geocoded} area(s), {$failed} failed.");

        return self::SUCCESS;
    }

    /** Geocoded once per city per run (cached in-memory) and used purely to sanity-check area matches - never stored. */
    private function cityReferencePoint(string $cityName, int &$requestCount): ?array
    {
        if (array_key_exists($cityName, $this->cityReferencePoints)) {
            return $this->cityReferencePoints[$cityName];
        }

        $point = $this->geocode("{$cityName}, Kenya", $cityName, null, $requestCount);

        return $this->cityReferencePoints[$cityName] = $point;
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function geocode(string $query, string $expectedName, ?array $nearPoint, int &$requestCount): ?array
    {
        if ($requestCount > 0) {
            // Be a polite, low-volume caller of a free shared public API.
            usleep(500_000);
        }
        $requestCount++;

        $response = Http::get('https://photon.komoot.io/api/', [
            'q' => $query,
            'limit' => 5,
            // Restrict to place/locality features (suburb, neighbourhood, town...)
            // so a search like "Karen" doesn't resolve to a landmark/POI named
            // Karen instead of the neighbourhood itself.
            'osm_tag' => 'place',
        ]);

        $features = $response->successful() ? ($response->json('features') ?? []) : [];

        // Only a genuinely same-named candidate counts - never fall back to
        // "whatever Photon ranked first" when nothing actually matches the
        // name (that's how "Muthaiga, Nanyuki" silently matched plain
        // "Nanyuki" the town, with no Muthaiga in sight).
        $candidates = collect($features)->filter(
            fn ($f) => strcasecmp($f['properties']['name'] ?? '', $expectedName) === 0
        );

        if ($nearPoint) {
            $inRange = $candidates->first(function ($f) use ($nearPoint) {
                [$lng, $lat] = $f['geometry']['coordinates'];

                return Geo::distanceKm($nearPoint['lat'], $nearPoint['lng'], $lat, $lng) <= self::MAX_DISTANCE_FROM_CITY_KM;
            });

            // Every same-named candidate existed, just none of them near this
            // city - a real place, just not the one we're looking for (e.g. a
            // "Milimani" that's actually in Nakuru, not the Meru one).
            $match = $inRange ?? null;
        } else {
            $match = $candidates->first();
        }

        if (! $match) {
            return null;
        }

        [$lng, $lat] = $match['geometry']['coordinates'];

        return ['lat' => $lat, 'lng' => $lng];
    }
}
