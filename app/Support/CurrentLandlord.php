<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class CurrentLandlord
{
    /**
     * Resolve the landlord_id that should scope the current request, or null when no
     * scoping should be applied at all (unauthenticated/console context - e.g. payment
     * webhooks, which resolve ownership themselves via their own reference IDs - an
     * authenticated superadmin, who must see every landlord's data - or a self-registered
     * 'user' account, which is deliberately landlord-less by design since it can browse
     * and apply across every landlord's public listings, not just one).
     */
    public static function id(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        if (in_array($user->role, ['superadmin', 'user'])) {
            return null;
        }

        return $user->landlord_id;
    }

    /**
     * True when the current request is authenticated as a user who is expected to have
     * a landlord_id (i.e. not superadmin/user, both intentionally landlord-less) but
     * doesn't - a mis-provisioned account. Scopes must fail closed in this case rather
     * than silently showing unfiltered data.
     */
    public static function shouldFailClosed(): bool
    {
        $user = Auth::user();

        return $user && !in_array($user->role, ['superadmin', 'user']) && !$user->landlord_id;
    }
}
