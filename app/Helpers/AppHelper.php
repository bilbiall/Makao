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
            // Falls back to the platform-wide app name (Platform Settings) before the
            // hardcoded config default, so a landlord who hasn't set their own still
            // gets a real business name in tenant-facing messages.
            $appName = Setting::effective($landlordId ?? CurrentLandlord::id(), 'app_name');

            if (filled($appName)) {
                return $appName;
            }
        } catch (\Throwable $e) {
            // Fallback if settings not available
        }

        return config('app.name', 'Renty');
    }
}
