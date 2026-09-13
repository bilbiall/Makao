<?php

namespace App\Services\Reports;

use App\Models\Tenant;
use App\Support\StaffScope;

/**
 * Every currently-admitted tenant with their unit and rent - the report a
 * landlord/PM hands a bank or investor to prove current occupancy and rent
 * income at a glance. Didn't exist anywhere in the app before this build.
 */
class RentRollReport
{
    public static function build(?int $locationId = null): array
    {
        $query = Tenant::with(['house.location']);
        StaffScope::onTenant($query);

        if ($locationId) {
            $query->whereHas('house', fn ($q) => $q->where('location_id', $locationId));
        }

        $tenants = $query->get();

        $properties = $tenants
            ->groupBy(fn (Tenant $tenant) => $tenant->house?->location?->location_name ?? 'Unassigned')
            ->map(function ($group, $locationName) {
                $rows = $group->map(fn (Tenant $tenant) => [
                    'tenant_name' => $tenant->tenant_name,
                    'phone_number' => $tenant->phone_number,
                    'unit' => $tenant->house?->display_name ?: $tenant->house?->house_name,
                    'rent_amount' => (float) ($tenant->house?->rent_amount ?? 0),
                    'balance' => (float) $tenant->balance,
                    'date_admitted' => $tenant->date_admitted,
                ])->sortBy('unit')->values();

                return [
                    'location_name' => $locationName,
                    'tenants' => $rows,
                    'subtotal_rent' => $rows->sum('rent_amount'),
                    'subtotal_balance' => $rows->sum('balance'),
                ];
            })
            ->sortBy('location_name')
            ->values();

        return [
            'properties' => $properties,
            'totals' => [
                'tenant_count' => $tenants->count(),
                'total_rent' => $properties->sum('subtotal_rent'),
                'total_balance' => $properties->sum('subtotal_balance'),
            ],
        ];
    }
}
