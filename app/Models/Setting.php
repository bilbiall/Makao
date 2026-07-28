<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    // Named "app_settings" (not "settings") to avoid colliding with the
    // tomatophp/filament-settings-hub package's own "settings" table.
    protected $table = 'app_settings';

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
