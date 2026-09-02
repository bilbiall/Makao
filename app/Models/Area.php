<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Area extends Model
{
    protected $fillable = ['city_id', 'name'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
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
