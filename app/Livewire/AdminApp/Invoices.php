<?php

namespace App\Livewire\AdminApp;

use App\Models\Invoice;
use App\Support\StaffScope;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $search = '';

    // Which invoice's detail popup is open, if any.
    public ?int $selectedInvoiceId = null;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function viewInvoice(int $invoiceId): void
    {
        $this->selectedInvoiceId = $invoiceId;
    }

    public function closeInvoiceModal(): void
    {
        $this->selectedInvoiceId = null;
    }

    public function getSelectedInvoiceProperty(): ?Invoice
    {
        if (!$this->selectedInvoiceId) {
            return null;
        }

        // Re-scoped the same way as the list below - a crafted selectedInvoiceId
        // must not leak an invoice outside this staff member's assigned properties.
        return StaffScope::onTenantChild(Invoice::query())
            ->with([
                'tenant',
                'payments' => fn ($q) => $q->latest('payment_date'),
            ])
            ->find($this->selectedInvoiceId);
    }

    protected function filteredQuery()
    {
        $query = StaffScope::onTenantChild(Invoice::query())->with('tenant')->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', $term)
                    ->orWhereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            });
        }

        return $query;
    }

    public function render()
    {
        $invoices = $this->filteredQuery()->paginate(10);

        // Stats reflect the full scoped invoice set, not just the current
        // status filter/search/page - the "big picture" summary at the top.
        $statsQuery = StaffScope::onTenantChild(Invoice::query());
        $totalInvoiced = (float) (clone $statsQuery)->sum('amount');
        $totalOutstanding = (float) (clone $statsQuery)->sum('balance');
        $totalPaid = $totalInvoiced - $totalOutstanding;

        $statusCounts = (clone $statsQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $trendLabels = [];
        $trendInvoiced = [];
        $trendPaid = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $trendLabels[] = $month->format('M');

            $monthAmount = (float) (clone $statsQuery)
                ->whereMonth('invoice_date', $month->month)
                ->whereYear('invoice_date', $month->year)
                ->sum('amount');
            $monthBalance = (float) (clone $statsQuery)
                ->whereMonth('invoice_date', $month->month)
                ->whereYear('invoice_date', $month->year)
                ->sum('balance');

            $trendInvoiced[] = $monthAmount;
            $trendPaid[] = $monthAmount - $monthBalance;
        }

        return view('livewire.admin-app.invoices', [
            'invoices' => $invoices,
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'paidCount' => (int) ($statusCounts['paid'] ?? 0),
            'partialCount' => (int) ($statusCounts['partial'] ?? 0),
            'unpaidCount' => (int) ($statusCounts['unpaid'] ?? 0),
            'trendLabels' => $trendLabels,
            'trendInvoiced' => $trendInvoiced,
            'trendPaid' => $trendPaid,
        ])->layout('components.layouts.app', ['title' => 'Invoices']);
    }
}
