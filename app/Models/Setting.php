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
        'landlord_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Each landlord has their own settings row (SMS/email templates, M-Pesa/Pesapal
     * credentials, app name) - these are business configuration, not shared platform
     * config, so no two landlords may see or affect each other's here.
     *
     * Pass null only for the system-level fallback row (used for superadmin's own
     * notifications, since a superadmin belongs to no landlord).
     */
    public static function forLandlord(?int $landlordId): self
    {
        return cache()->rememberForever("settings_for_landlord_{$landlordId}", function () use ($landlordId) {
            return self::firstOrCreate(['landlord_id' => $landlordId]);
        });
    }

    protected static function booted(): void
    {
        static::saved(function (self $setting) {
            cache()->forget("settings_for_landlord_{$setting->landlord_id}");
        });
    }
}
