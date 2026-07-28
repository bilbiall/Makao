<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param string $action
     * @param int|null $userId
     * @param string|null $details
     * @return ActivityLog
     */
    public static function log(string $action, ?int $userId = null, ?string $details = null)
    {
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip' => Request::ip(),
        ]);
    }
}
