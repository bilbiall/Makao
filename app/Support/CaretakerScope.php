<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Caretakers are staff narrowed to a single location - this mirrors the exact
 * getEloquentQuery() scoping already used by each Filament admin resource
 * (TenantResource, HouseResource, InvoiceResource, etc.) so the new app-shell
 * pages enforce the identical restriction rather than re-deriving it.
 */
class CaretakerScope
{
    protected static function user()
    {
        return Auth::user();
    }

    protected static function isCaretaker(): bool
    {
        $user = static::user();
        return $user && $user->role === 'caretaker' && $user->location_id;
    }

    /** For models with a direct location_id column (House). */
    public static function onHouse(Builder $query): Builder
    {
        if (static::isCaretaker()) {
            $query->where('location_id', static::user()->location_id);
        }
        return $query;
    }

    /** For models with a house() relation (Tenant). */
    public static function onTenant(Builder $query): Builder
    {
        if (static::isCaretaker()) {
            $locationId = static::user()->location_id;
            $query->whereHas('house', fn ($q) => $q->where('location_id', $locationId));
        }
        return $query;
    }

    /** For models with a tenant.house relation chain (Invoice, Payment, Bill, Issue, NoticeToVacate). */
    public static function onTenantChild(Builder $query): Builder
    {
        if (static::isCaretaker()) {
            $locationId = static::user()->location_id;
            $query->whereHas('tenant.house', fn ($q) => $q->where('location_id', $locationId));
        }
        return $query;
    }
}
