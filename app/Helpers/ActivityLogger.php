<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use App\Models\User;
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
        // Derived from $userId (the actual actor), not CurrentLandlord::id() - a
        // console/queued context has no authenticated session for the latter to
        // read, and $userId is passed explicitly by every caller anyway. Left
        // null (visible only to superadmin) when there's no user to attribute it
        // to at all.
        $landlordId = $userId ? User::withoutGlobalScopes()->find($userId)?->landlord_id : null;

        return ActivityLog::create([
            'user_id' => $userId,
            'landlord_id' => $landlordId,
            'action' => $action,
            'details' => $details,
            'ip' => Request::ip(),
        ]);
    }
}
