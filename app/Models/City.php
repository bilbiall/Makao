<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name'];

    public function areas()
    {
        return $this->hasMany(Area::class)->orderBy('name');
    }

    /** Every city with its areas loaded - the single source for every location-search dropdown. */
    public static function breakdown()
    {
        return static::with('areas')->orderBy('name')->get();
    }
}
