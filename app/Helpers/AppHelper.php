<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Support\CurrentLandlord;

class AppHelper
{
    /**
     * Get application name from settings or fallback to config. A landlord can set
     * their own business name here to appear in their own tenant-facing messages
     * (the {app_name} placeholder), instead of the generic platform name.
     */
    public static function getAppName(?int $landlordId = null): string
    {
        try {
            $settings = Setting::forLandlord($landlordId ?? CurrentLandlord::id());
            $payload = $settings->payload ?? [];
            
            if (!empty($payload['app_name'])) {
                return $payload['app_name'];
            }
        } catch (\Throwable $e) {
            // Fallback if settings not available
        }
        
        return config('app.name', 'Renty');
    }
}
