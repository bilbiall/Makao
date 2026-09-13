<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Support\StaffScope;
use Carbon\Carbon;

/**
 * Aging report for unpaid rent: every overdue invoice bucketed by how many
 * days past due_date it is. Uses Invoice.balance (already tracked per-invoice)
 * rather than Tenant.balance, so a tenant with several overdue invoices shows
 * each one's own age instead of a single blended number. Didn't exist anywhere
 * in the app before this build.
 */
class ArrearsReport
{
    public const BUCKETS = [
        'current' => '1-30 days',
        'bucket_31_60' => '31-60 days',
        'bucket_61_90' => '61-90 days',
        'bucket_90_plus' => '90+ days',
    ];

    public static function build(?int $locationId = null): array
    {
        $today = Carbon::today();

        $query = Invoice::with(['tenant.house.location'])
            ->where('status', '!=', 'paid')
            ->where('balance', '>', 0)
            ->where('due_date', '<', $today->toDateString());

        StaffScope::onTenantChild($query);

        if ($locationId) {
            $query->whereHas('tenant.house', fn ($q) => $q->where('location_id', $locationId));
        }

        $rows = $query->get()->map(function (Invoice $invoice) use ($today) {
            $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays($today);
            $bucket = match (true) {
                $daysOverdue <= 30 => 'current',
                $daysOverdue <= 60 => 'bucket_31_60',
                $daysOverdue <= 90 => 'bucket_61_90',
                default => 'bucket_90_plus',
            };

            return [
                'tenant_name' => $invoice->tenant?->tenant_name ?? 'Unknown tenant',
                'phone_number' => $invoice->tenant?->phone_number,
                'location_name' => $invoice->tenant?->house?->location?->location_name ?? 'Unassigned',
                'unit' => $invoice->tenant?->house?->display_name ?: $invoice->tenant?->house?->house_name,
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date,
                'days_overdue' => $daysOverdue,
                'balance' => (float) $invoice->balance,
                'bucket' => $bucket,
                'bucket_label' => self::BUCKETS[$bucket],
            ];
        })->sortByDesc('days_overdue')->values();

        $buckets = collect(self::BUCKETS)->map(function ($label, $key) use ($rows) {
            $inBucket = $rows->where('bucket', $key);

            return [
                'key' => $key,
                'label' => $label,
                'count' => $inBucket->count(),
                'total' => $inBucket->sum('balance'),
            ];
        })->values();

        return [
            'rows' => $rows,
            'buckets' => $buckets,
            'invoice_count' => $rows->count(),
            'tenant_count' => $rows->pluck('tenant_name')->unique()->count(),
            'grand_total' => $rows->sum('balance'),
        ];
    }
}
