<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Always return the singleton settings row.
     * Creates it if it does not exist.
     */
    public static function singleton(): self
    {
        return cache()->rememberForever('settings_singleton', function () {
            return self::firstOrCreate([]);
        });
    }
}
