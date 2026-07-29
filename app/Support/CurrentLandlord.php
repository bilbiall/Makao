<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class CurrentLandlord
{
    /**
     * Resolve the landlord_id that should scope the current request, or null when no
     * scoping should be applied at all (unauthenticated/console context - e.g. payment
     * webhooks, which resolve ownership themselves via their own reference IDs - or an
     * authenticated superadmin, who must see every landlord's data).
     */
    public static function id(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        if ($user->role === 'superadmin') {
            return null;
        }

        return $user->landlord_id;
    }

    /**
     * True when the current request is authenticated as a non-superadmin user who
     * nonetheless has no landlord_id - a mis-provisioned account. Scopes must fail
     * closed in this case rather than silently showing unfiltered data.
     */
    public static function shouldFailClosed(): bool
    {
        $user = Auth::user();

        return $user && $user->role !== 'superadmin' && !$user->landlord_id;
    }
}
