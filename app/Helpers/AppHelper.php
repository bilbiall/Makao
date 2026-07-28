<?php

namespace App\Helpers;

use App\Models\Setting;

class AppHelper
{
    /**
     * Get application name from settings or fallback to config
     */
    public static function getAppName(): string
    {
        try {
            $settings = Setting::singleton();
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
