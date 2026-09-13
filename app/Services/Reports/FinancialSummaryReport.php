<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\StaffScope;
use Carbon\Carbon;

/**
 * The invoiced-vs-paid monthly rollup that used to be the entirety of "Reports"
 * (App\Filament\Pages\Reports / the old App\Livewire\AdminApp\Reports). Same
 * query logic as before, plus a location filter and per-location subtotals on
 * the invoice list, since "one flat unfiltered table" was the main complaint.
 */
class FinancialSummaryReport
{
    public static function build(?string $from, ?string $to, ?int $locationId = null, ?string $tenantSearch = null, ?string $invoiceStatus = null): array
    {
        [$start, $end] = ReportPeriod::bounds($from, $to);
        $months = ReportPeriod::months($start, $end);

        $labels = [];
        $invoiceTotals = [];
        $paymentTotals = [];

        foreach ($months as $m) {
            [$periodStart, $periodEnd] = ReportPeriod::monthRange($m);
            $labels[] = ReportPeriod::monthLabel($m);

            $invoiceQuery = Invoice::whereBetween('invoice_date', [$periodStart, $periodEnd]);
            $paymentQuery = Payment::whereBetween('payment_date', [$periodStart, $periodEnd]);
            StaffScope::onTenantChild($invoiceQuery);
            StaffScope::onTenantChild($paymentQuery);

            if ($locationId) {
                $invoiceQuery->whereHas('tenant.house', fn ($q) => $q->where('location_id', $locationId));
                $paymentQuery->whereHas('tenant.house', fn ($q) => $q->where('location_id', $locationId));
            }

            $invoiceTotals[] = (float) $invoiceQuery->sum('amount');
            $paymentTotals[] = (float) $paymentQuery->sum('amount_paid');
        }

        $totalInvoiced = array_sum($invoiceTotals);
        $totalPaid = array_sum($paymentTotals);

        $invoicesQuery = Invoice::with(['tenant.house.location', 'payments'])
            ->whereBetween('invoice_date', [$start, $end])
            ->orderBy('invoice_date', 'desc');

        StaffScope::onTenantChild($invoicesQuery);

        if ($locationId) {
            $invoicesQuery->whereHas('tenant.house', fn ($q) => $q->where('location_id', $locationId));
        }

        if ($tenantSearch) {
            $term = '%' . $tenantSearch . '%';
            $invoicesQuery->whereHas('tenant', function ($q) use ($term) {
                $q->where('tenant_name', 'like', $term)->orWhere('phone_number', 'like', $term);
            });
        }

        $statusLabel = null;
        if ($invoiceStatus) {
            $today = Carbon::now()->toDateString();
            match ($invoiceStatus) {
                'overdue' => $invoicesQuery->where('status', '!=', 'paid')->where('due_date', '<', $today),
                'due' => $invoicesQuery->where('status', '!=', 'paid')->whereDate('due_date', '=', $today),
                'upcoming' => $invoicesQuery->where('status', '!=', 'paid')->where('due_date', '>', $today),
                'paid' => $invoicesQuery->where('status', 'paid'),
                'partial' => $invoicesQuery->where('status', 'partial'),
                'unpaid' => $invoicesQuery->where('status', 'unpaid'),
                default => null,
            };
            $statusLabel = [
                'overdue' => 'Overdue', 'due' => 'Due Today', 'upcoming' => 'Upcoming',
                'paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid',
            ][$invoiceStatus] ?? null;
        }

        $invoices = $invoicesQuery->get();

        $byProperty = $invoices->groupBy(fn (Invoice $invoice) => $invoice->tenant?->house?->location?->location_name ?? 'Unassigned')
            ->map(function ($group, $locationName) {
                return [
                    'location_name' => $locationName,
                    'invoices' => $group->values(),
                    'subtotal_invoiced' => (float) $group->sum('amount'),
                    'subtotal_balance' => (float) $group->sum('balance'),
                ];
            })
            ->sortBy('location_name')
            ->values();

        return [
            'from' => $start,
            'to' => $end,
            'labels' => $labels,
            'invoice_totals' => $invoiceTotals,
            'payment_totals' => $paymentTotals,
            'summary' => [
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'outstanding' => max(0, $totalInvoiced - $totalPaid),
                'collection_rate' => $totalInvoiced > 0 ? round(($totalPaid / $totalInvoiced) * 100, 1) : 0,
            ],
            'invoices' => $invoices,
            'by_property' => $byProperty,
            'status_label' => $statusLabel,
        ];
    }
}
