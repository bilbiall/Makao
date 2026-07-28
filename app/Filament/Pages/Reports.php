<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Reports';
    protected static ?string $slug = 'reports';
    protected static ?string $navigationGroup = 'Analytics';
    protected static string $view = 'filament.pages.reports';

    /**
     * Role-based access: Caretaker cannot access Reports.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'manager']);
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['admin', 'manager'])) {
            abort(403, 'Unauthorized');
        }

        // Default to last 6 months
        $this->to = request()->query('to') ? Carbon::parse(request()->query('to')) : Carbon::now();
        $this->from = request()->query('from') ? Carbon::parse(request()->query('from')) : (clone $this->to)->subMonths(5)->startOfMonth();
        $this->tenant_search = request()->query('tenant_search');
        $this->invoice_status = request()->query('invoice_status', '');

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

        $this->labels = array_map(fn($m) => Carbon::parse($m . '-01')->format('M Y'), $months);

        $this->invoiceTotals = [];
        $this->paymentTotals = [];

        foreach ($months as $m) {
            $periodStart = Carbon::parse($m . '-01')->startOfMonth();
            $periodEnd = Carbon::parse($m . '-01')->endOfMonth();

            $invTotal = Invoice::whereBetween('invoice_date', [$periodStart, $periodEnd])->sum('amount');
            $payTotal = Payment::whereBetween('payment_date', [$periodStart, $periodEnd])->sum('amount_paid');

            $this->invoiceTotals[] = (float) $invTotal;
            $this->paymentTotals[] = (float) $payTotal;
        }

        $totalInvoiced = array_sum($this->invoiceTotals);
        $totalPaid = array_sum($this->paymentTotals);
        $outstanding = max(0, $totalInvoiced - $totalPaid);

        $this->summary = [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'outstanding' => $outstanding,
        ];

        // Fetch invoice list for the table filtered by the same period and optional tenant search
        $invoicesQuery = Invoice::with(['tenant', 'payments'])
            ->whereBetween('invoice_date', [$start, $end])
            ->orderBy('invoice_date', 'desc');

        if ($this->tenant_search) {
            $term = '%' . $this->tenant_search . '%';
            $invoicesQuery->whereHas('tenant', function ($q) use ($term) {
                $q->where('tenant_name', 'like', $term)
                  ->orWhere('phone_number', 'like', $term);
            });
        }

        // Apply invoice status filters
        if ($this->invoice_status) {
            switch ($this->invoice_status) {
                case 'overdue':
                    $invoicesQuery->where('status', '!=', 'paid')
                        ->where('due_date', '<', Carbon::now()->toDateString());
                    $this->invoice_status_label = 'Overdue';
                    break;
                case 'due':
                    $invoicesQuery->where('status', '!=', 'paid')
                        ->whereDate('due_date', '=', Carbon::now()->toDateString());
                    $this->invoice_status_label = 'Due Today';
                    break;
                case 'upcoming':
                    $invoicesQuery->where('status', '!=', 'paid')
                        ->where('due_date', '>', Carbon::now()->toDateString());
                    $this->invoice_status_label = 'Upcoming';
                    break;
                case 'paid':
                    $invoicesQuery->where('status', 'paid');
                    $this->invoice_status_label = 'Paid';
                    break;
                case 'partial':
                    $invoicesQuery->where('status', 'partial');
                    $this->invoice_status_label = 'Partial';
                    break;
                case 'unpaid':
                    $invoicesQuery->where('status', 'unpaid');
                    $this->invoice_status_label = 'Unpaid';
                    break;
            }
        }

        $this->invoices = $invoicesQuery->get();
    }

    public function updatedFrom(): void
    {
        $this->buildStats();
    }

    public function updatedTo(): void
    {
        $this->buildStats();
    }

    public function updatedTenantSearch(): void
    {
        $this->buildStats();
    }

    public function updatedInvoiceStatus(): void
    {
        $this->buildStats();
    }

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
}
