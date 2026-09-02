<?php

namespace App\Livewire\AdminApp;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Full parity with App\Filament\Pages\Reports - the app-shell is the default UI for
 * every role, so this can't be a trimmed summary with a link out to Filament for the
 * real functionality. Same query logic as the Filament page, hand-rolled as plain
 * Blade/Livewire to match every other page in this app-shell.
 */
class Reports extends Component
{
    public $from;
    public $to;
    public $tenant_search;
    public $invoice_status = '';
    public $invoice_status_label;
    public $labels = [];
    public $invoiceTotals = [];
    public $paymentTotals = [];
    public $summary = [];
    public $invoices;

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);

        $this->to = Carbon::now();
        $this->from = (clone $this->to)->subMonths(5)->startOfMonth();

        $this->buildStats();
    }

    public function buildStats(): void
    {
        $start = Carbon::parse($this->from)->startOfMonth();
        $end = Carbon::parse($this->to)->endOfMonth();

        $months = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $this->labels = array_map(fn ($m) => Carbon::parse($m . '-01')->format('M Y'), $months);

        $this->invoiceTotals = [];
        $this->paymentTotals = [];

        foreach ($months as $m) {
            $periodStart = Carbon::parse($m . '-01')->startOfMonth();
            $periodEnd = Carbon::parse($m . '-01')->endOfMonth();

            $this->invoiceTotals[] = (float) Invoice::whereBetween('invoice_date', [$periodStart, $periodEnd])->sum('amount');
            $this->paymentTotals[] = (float) Payment::whereBetween('payment_date', [$periodStart, $periodEnd])->sum('amount_paid');
        }

        $totalInvoiced = array_sum($this->invoiceTotals);
        $totalPaid = array_sum($this->paymentTotals);

        $this->summary = [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'outstanding' => max(0, $totalInvoiced - $totalPaid),
        ];

        $invoicesQuery = Invoice::with(['tenant', 'payments'])
            ->whereBetween('invoice_date', [$start, $end])
            ->orderBy('invoice_date', 'desc');

        if ($this->tenant_search) {
            $term = '%' . $this->tenant_search . '%';
            $invoicesQuery->whereHas('tenant', function ($q) use ($term) {
                $q->where('tenant_name', 'like', $term)->orWhere('phone_number', 'like', $term);
            });
        }

        $this->invoice_status_label = null;
        if ($this->invoice_status) {
            $today = Carbon::now()->toDateString();
            match ($this->invoice_status) {
                'overdue' => $invoicesQuery->where('status', '!=', 'paid')->where('due_date', '<', $today),
                'due' => $invoicesQuery->where('status', '!=', 'paid')->whereDate('due_date', '=', $today),
                'upcoming' => $invoicesQuery->where('status', '!=', 'paid')->where('due_date', '>', $today),
                'paid' => $invoicesQuery->where('status', 'paid'),
                'partial' => $invoicesQuery->where('status', 'partial'),
                'unpaid' => $invoicesQuery->where('status', 'unpaid'),
                default => null,
            };
            $this->invoice_status_label = [
                'overdue' => 'Overdue', 'due' => 'Due Today', 'upcoming' => 'Upcoming',
                'paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid',
            ][$this->invoice_status] ?? null;
        }

        $this->invoices = $invoicesQuery->get();
    }

    public function updatedFrom(): void { $this->buildStats(); }
    public function updatedTo(): void { $this->buildStats(); }
    public function updatedTenantSearch(): void { $this->buildStats(); }
    public function updatedInvoiceStatus(): void { $this->buildStats(); }

    public function exportPdf()
    {
        $this->buildStats();

        $html = view('filament.reports.pdf', [
            'invoices' => $this->invoices,
            'summary' => $this->summary,
            'from' => $this->from,
            'to' => $this->to,
            'status_label' => $this->invoice_status_label,
        ])->render();

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, 'invoices-report-' . now()->format('Y-m-d') . '.pdf', [
            'Content-Type' => 'text/html',
        ]);
    }

    public function exportExcel()
    {
        $this->buildStats();

        return response()->json([
            'invoices' => $this->invoices,
            'summary' => $this->summary,
        ]);
    }

    public function render()
    {
        return view('livewire.admin-app.reports')
            ->layout('components.layouts.app', ['title' => 'Reports']);
    }
}
