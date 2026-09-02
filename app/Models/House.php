<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ActivityLogger;
use App\Models\Concerns\BelongsToLandlord;


class House extends Model
{
    //
    use HasFactory, BelongsToLandlord;

    // Canonical unit-type options offered wherever a unit's type is picked from a
    // dropdown (Units::addUnit(), its search filter) - keeps the value free-text in
    // the column (house_type) but consistent enough to filter/search on reliably.
    public const UNIT_TYPES = [
        'Single Room',
        'Bedsitter',
        'Studio',
        '1 Bedroom',
        '2 Bedroom',
        '3 Bedroom',
        '4 Bedroom',
        'Maisonette',
        'Townhouse',
        'Own Compound',
    ];

    // A fixed, curated list - not free text - so every listing's amenities stay
    // consistent and searchable/filterable later, and so this reads as amenities
    // relevant to Kenyan rentals specifically rather than a generic Western list.
    public const AMENITIES = [
        'Borehole water',
        'Backup water tank',
        'Backup generator',
        'Secure parking',
        'CCTV',
        'Electric fence',
        'Perimeter wall',
        'Wi-Fi',
        'Balcony',
        'Lift',
        'Master ensuite',
        'Gym',
        'Swimming pool',
        'DSQ (servant quarter)',
        'Garden',
        'Pet friendly',
        'Kitchenette',
        'Furnished',
        'Air conditioning',
        'Washing machine allowed',
        'DSTV/Netflix ready',
        'Self check-in',
    ];

    // Slug => label for the "how far is X" section on a listing - fixed, not
    // free text, for the same reason AMENITIES is: consistent, and specific to
    // what actually matters when house-hunting in a Kenyan town. Each holds an
    // optional {minutes, km} pair in House.nearby_places - see nearbyPlaces().
    public const NEARBY_CATEGORIES = [
        'school' => 'School',
        'hospital' => 'Hospital/Clinic',
        'mall' => 'Shopping mall',
        'supermarket' => 'Supermarket',
        'market' => 'Local market',
        'bus_stage' => 'Bus/matatu stage',
        'main_road' => 'Main road/highway',
        'place_of_worship' => 'Church/mosque',
        'bank_atm' => 'Bank/ATM',
        'police_station' => 'Police station',
    ];

    protected $fillable = [
        'house_name',
        'display_name',
        //'number_of_rooms',
        'rent_amount',
        'location_id',
        'house_type',
        'house_status',
        'landlord_id',
        'description',
        'size_label',
        'listing_mode',
        'amenities',
        'nearby_places',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'nearby_places' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /** What a renter should see as this unit's name - falls back to the internal house_name when no display_name is set. */
    public function publicName(): string
    {
        return $this->display_name ?: $this->house_name;
    }

    /**
     * Only the categories the owner actually filled in (minutes away, on
     * foot/by matatu - whichever's the natural way to describe it), in
     * House::NEARBY_CATEGORIES' own order - e.g.
     * ['school' => ['label' => 'School', 'minutes' => 5], ...].
     */
    public function nearbyPlacesForDisplay(): array
    {
        $raw = $this->nearby_places ?? [];
        $result = [];

        foreach (self::NEARBY_CATEGORIES as $slug => $label) {
            $minutes = $raw[$slug] ?? null;

            if ($minutes !== null && $minutes !== '') {
                $result[$slug] = ['label' => $label, 'minutes' => (int) $minutes];
            }
        }

        return $result;
    }

    //relationship with the location model
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    //relationship with the tenant model
    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function photos()
    {
        return $this->hasMany(HousePhoto::class)->orderBy('sort_order');
    }

    // BnB pricing tiers (Nightly/Weekly/Monthly, etc). Only meaningful when
    // listing_mode is 'short_term' - the actual booking/reservation engine that
    // consumes these prices is Phase 2, not implemented yet.
    public function pricePackages()
    {
        return $this->hasMany(HousePricePackage::class)->orderBy('sort_order');
    }

    public function isShortTerm(): bool
    {
        return $this->listing_mode === 'short_term';
    }

    public function watchlistedBy()
    {
        return $this->belongsToMany(User::class, 'house_user_watchlist')->withTimestamps();
    }

    public function viewingRequests()
    {
        return $this->hasMany(ViewingRequest::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Powers the "area" search filter on /houses and /stays - $input can be either
     * a specific area name (e.g. "Nyali", exact match against Location.geo_id, same
     * as before) or a city/town name (e.g. "Mombasa"), in which case it matches
     * every house whose Location is linked to ANY area within that city. Falls
     * back to a plain geo_id match for anything that isn't a known city name,
     * including areas typed before the city/area master list existed.
     */
    public function scopeInAreaOrCity($query, string $input)
    {
        $city = \App\Models\City::whereRaw('LOWER(name) = ?', [strtolower($input)])->first();

        if ($city) {
            return $query->whereHas('location.area', fn ($q) => $q->where('city_id', $city->id));
        }

        return $query->whereHas('location', fn ($q) => $q->where('geo_id', $input));
    }

    /**
     * Live "N available" counts per canonical Area, for the location-search
     * dropdown's pulsing badges - $mode is 'long_term' (publiclyVisible) or
     * 'short_term' (bnbVisible). Only ever reflects houses linked to a master
     * Area record (whereHas('location.area')) - a location with a custom,
     * un-seeded geo_id simply has no badge anywhere, which is correct since the
     * dropdown itself only ever suggests seeded areas.
     */
    public static function availabilityCountsByArea(string $mode): array
    {
        $query = $mode === 'short_term' ? static::bnbVisible() : static::publiclyVisible();

        return $query->whereHas('location.area')
            ->with('location.area:id,name')
            ->get()
            ->groupBy(fn (House $house) => $house->location->area->name)
            ->map->count()
            ->all();
    }

    /**
     * Public discovery visibility is mostly derived (Vacant, minimum listing info
     * filled in, disappears for free via the same house_status flips
     * Tenant::booted() already does on admission/vacate) but gated on top by
     * is_published - the owner's manual on/off switch. Turning a unit off hides it
     * from every public surface (home page, /houses or /stays search, "all units")
     * without touching occupancy/tenancy; turning it back on makes it searchable
     * again immediately, same as any other eligible unit.
     */
    public function scopePubliclyVisible($query)
    {
        return $query->where('house_status', 'Vacant')
            ->where('listing_mode', 'long_term')
            ->where('is_published', true)
            ->whereNotNull('rent_amount')
            ->whereHas('photos')
            ->whereHas('location.landlord', fn ($q) => $q->where('status', '!=', 'suspended'));
    }

    /**
     * Short-stay (BnB) equivalent of scopePubliclyVisible() - a short_term house needs
     * no vacancy check (occupancy lives in the bookings calendar, not house_status).
     */
    public function scopeBnbVisible($query)
    {
        return $query->where('listing_mode', 'short_term')
            ->where('is_published', true)
            ->whereHas('photos')
            ->whereHas('location.landlord', fn ($q) => $q->where('status', '!=', 'suspended'));
    }

    protected static function booted()
    {
        static::creating(function ($house) {
            if (!$house->landlord_id && $house->location_id) {
                $house->landlord_id = \App\Models\Location::withoutGlobalScopes()->find($house->location_id)?->landlord_id;
            }
        });

        static::creating(function ($house) {
            $landlord = $house->landlord_id ? \App\Models\Landlord::find($house->landlord_id) : null;
            $limitService = app(\App\Services\PackageLimitService::class);

            if ($landlord && !$limitService->canAdd('houses', $landlord)) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('Plan limit reached')
                    ->body($limitService->limitMessage('houses', $landlord))
                    ->send();

                return false;
            }
        });

        static::created(function ($house) {
            try {
                $actor = auth()->id() ?? null;
                $details = "House created: {$house->house_name} (Rent: {$house->rent_amount})";
                ActivityLogger::log('create_house', $actor, $details);

                // Notify this landlord's own admins about the new house
                $admins = \App\Models\User::where('role', 'admin')->where('landlord_id', $house->landlord_id)->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\DatabaseNotification(
                        'House Created',
                        $details,
                        null
                    ));
                }
            } catch (\Throwable $e) {
                // ignore logging errors
            }
        });
        
        static::updated(function ($house) {
            try {
                $actor = auth()->id() ?? null;
                $changes = [];
                
                if ($house->wasChanged('house_name')) {
                    $changes[] = "name from '{$house->getOriginal('house_name')}' to '{$house->house_name}'";
                }
                if ($house->wasChanged('rent_amount')) {
                    $changes[] = "rent from {$house->getOriginal('rent_amount')} to {$house->rent_amount}";
                }
                if ($house->wasChanged('house_status')) {
                    $changes[] = "status from '{$house->getOriginal('house_status')}' to '{$house->house_status}'";
                }
                if ($house->wasChanged('location_id')) {
                    $oldLocation = \App\Models\Location::find($house->getOriginal('location_id'));
                    $newLocation = $house->location;
                    $changes[] = "location from '{$oldLocation->location_name}' to '{$newLocation->location_name}'";
                }
                
                if (!empty($changes)) {
                    $details = "Updated house {$house->house_name}: " . implode(', ', $changes);
                    \App\Helpers\ActivityLogger::log('update_house', $actor, $details);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        });
        
        static::deleted(function ($house) {
            try {
                $actor = auth()->id() ?? null;
                $details = "House {$house->house_name} deleted (Rent: {$house->rent_amount})";
                \App\Helpers\ActivityLogger::log('delete_house', $actor, $details);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
