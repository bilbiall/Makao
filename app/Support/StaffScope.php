<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Manager and Caretaker staff are narrowed to whichever Locations (properties)
 * they've been granted via the staff_assignments pivot (App\Models\StaffAssignment)
 * - replaces the old CaretakerScope, which only supported a single location per
 * user via User.location_id. Both roles share identical scoping for now; only the
 * role label differs (see the Phase 1 plan for why).
 */
class StaffScope
{
    protected static function user()
    {
        return Auth::user();
    }

    public static function isScopedStaff(): bool
    {
        $user = static::user();
        return $user && in_array($user->role, ['caretaker', 'manager']);
    }

    public static function isAgent(): bool
    {
        $user = static::user();
        return $user && $user->role === 'agent';
    }

    public static function locationIds(): array
    {
        return static::user()?->staffAssignments()->pluck('location_id')->all() ?? [];
    }

    /** Agent's directly-assigned short_term house ids (as opposed to a whole Location). */
    public static function houseIds(): array
    {
        return static::user()?->staffAssignments()->whereNotNull('house_id')->pluck('house_id')->all() ?? [];
    }

    /**
     * Agent is scoped to specific short_term houses for bookings only (see
     * onHouseOrAssignedHouse()) - it has no legitimate access to tenants, invoices,
     * payments, bills, issues, or notices at all, so every other helper below fails
     * closed for it instead of falling through unfiltered (which is correct only for
     * landlord/admin/superadmin).
     */
    protected static function denyIfAgent(Builder $query): ?Builder
    {
        if (static::isAgent()) {
            $query->whereRaw('1 = 0');
            return $query;
        }
        return null;
    }

    /** For models reachable via a custom relation chain not covered by the helpers above
     *  (e.g. MpesaTransaction's invoice.tenant.house). Pass the relation path as a string. */
    public static function onRelation(Builder $query, string $relation, string $column = 'location_id'): Builder
    {
        if ($denied = static::denyIfAgent($query)) {
            return $denied;
        }
        if (static::isScopedStaff()) {
            $locationIds = static::locationIds();
            $query->whereHas($relation, fn ($q) => $q->whereIn($column, $locationIds));
        }
        return $query;
    }

    /** For models with a direct location_id column (House). */
    public static function onHouse(Builder $query): Builder
    {
        if ($denied = static::denyIfAgent($query)) {
            return $denied;
        }
        if (static::isScopedStaff()) {
            $query->whereIn('location_id', static::locationIds());
        }
        return $query;
    }

    /** For models with a house() relation (Tenant). */
    public static function onTenant(Builder $query): Builder
    {
        if ($denied = static::denyIfAgent($query)) {
            return $denied;
        }
        if (static::isScopedStaff()) {
            $locationIds = static::locationIds();
            $query->whereHas('house', fn ($q) => $q->whereIn('location_id', $locationIds));
        }
        return $query;
    }

    /** For models with a tenant.house relation chain (Invoice, Payment, Bill, Issue, NoticeToVacate). */
    public static function onTenantChild(Builder $query): Builder
    {
        if ($denied = static::denyIfAgent($query)) {
            return $denied;
        }
        if (static::isScopedStaff()) {
            $locationIds = static::locationIds();
            $query->whereHas('tenant.house', fn ($q) => $q->whereIn('location_id', $locationIds));
        }
        return $query;
    }

    /**
     * For Booking (and BookingResource/the Bookings app-shell page): Manager/Caretaker
     * see every house in their assigned properties, Agent sees only their directly
     * assigned house(s). Unscoped for landlord/admin/superadmin, same as every other helper.
     */
    public static function onHouseOrAssignedHouse(Builder $query): Builder
    {
        if (static::isScopedStaff()) {
            $locationIds = static::locationIds();
            $query->whereHas('house', fn ($q) => $q->whereIn('location_id', $locationIds));
        } elseif (static::isAgent()) {
            $houseIds = static::houseIds();
            $query->whereIn('house_id', $houseIds);
        }
        return $query;
    }
}
