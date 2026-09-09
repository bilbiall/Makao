<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'is_open'];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    public function areas()
    {
        return $this->hasMany(Area::class)->orderBy('name');
    }

    /**
     * Every OPEN city with its areas loaded - the single source for every
     * location-search dropdown, both public discovery and property-creation
     * pickers. A city not yet opened by a superadmin (Superadmin > Locations)
     * simply doesn't appear as pickable anywhere - existing data referencing
     * it (a Location's geo_id, e.g.) is untouched either way, this only
     * governs what shows up as a choice going forward.
     */
    public static function breakdown()
    {
        return static::where('is_open', true)->with('areas')->orderBy('name')->get();
    }
}
