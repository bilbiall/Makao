<?php

namespace App\Models;

use App\Support\Geo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Area extends Model
{
    protected $fillable = ['city_id', 'name', 'latitude', 'longitude'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Other geocoded areas within $radiusKm, closest first. Areas without
     * coordinates yet (not geocoded) never appear here or as a match - this
     * only ever adds suggestions on top of the existing exact-name search,
     * never replaces it.
     */
    public function nearby(float $radiusKm = 3.0): Collection
    {
        if ($this->latitude === null || $this->longitude === null) {
            return collect();
        }

        return static::query()
            ->whereKeyNot($this->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn (Area $area) => $area->setAttribute(
                'distance_km',
                Geo::distanceKm($this->latitude, $this->longitude, $area->latitude, $area->longitude)
            ))
            ->filter(fn (Area $area) => $area->distance_km <= $radiusKm)
            ->sortBy('distance_km')
            ->values();
    }

    /**
     * Every seeded area name, plus any extra (typically a page's own distinct
     * `geo_id` values) so a location typed before this master list existed - or
     * one in a city we haven't seeded yet - still shows up as a suggestion.
     * Used to power every "search a location" datalist in the app.
     */
    public static function suggestionNames(?Collection $extra = null): Collection
    {
        return static::query()
            ->orderBy('name')
            ->pluck('name')
            ->merge($extra ?? collect())
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
