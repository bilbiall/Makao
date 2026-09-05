<?php

namespace App\Livewire\AdminApp;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\StaffScope;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public $tenant_id = '';
    public $invoice_id = '';
    public $amount_paid = '';
    public string $payment_reference = '';
    public string $payment_method = 'cash';
    public string $payment_date = '';

    public string $search = '';

    // Which payment's detail popup is open, if any.
    public ?int $selectedPaymentId = null;

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTenantId(): void
    {
        $this->invoice_id = '';
    }

    public function viewPayment(int $paymentId): void
    {
        $this->selectedPaymentId = $paymentId;
    }

    public function closePaymentModal(): void
    {
        $this->selectedPaymentId = null;
    }

    public function getSelectedPaymentProperty(): ?Payment
    {
        if (!$this->selectedPaymentId) {
            return null;
        }

        return StaffScope::onTenantChild(Payment::query())
            ->with(['tenant', 'invoice'])
            ->find($this->selectedPaymentId);
    }

    protected function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'invoice_id' => 'required|exists:invoices,id',
            'amount_paid' => 'required|numeric|min:1',
            'payment_reference' => 'required|string|max:255',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
        ];
    }

    public function record(): void
    {
        $this->validate();

        // Creating this record triggers Payment::booted()'s static::created hook,
        // which recalculates the invoice's balance/status, the tenant's running
        // balance, sends the SMS confirmation, and logs the activity - the exact
        // same path Filament's PaymentResource create form uses, so recording a
        // payment here behaves identically regardless of which UI was used.
        Payment::create([
            'tenant_id' => $this->tenant_id,
            'invoice_id' => $this->invoice_id,
            'amount_paid' => $this->amount_paid,
            'payment_reference' => $this->payment_reference,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date,
        ]);

        $this->reset(['tenant_id', 'invoice_id', 'amount_paid', 'payment_reference', 'showForm']);
        $this->payment_method = 'cash';
        $this->payment_date = now()->format('Y-m-d');
        session()->flash('payment-recorded', 'Payment recorded successfully.');
    }

    protected function filteredQuery()
    {
        $query = StaffScope::onTenantChild(Payment::query())->with(['tenant', 'invoice'])->latest();

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('payment_reference', 'like', $term)
                    ->orWhereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', $term)->orWhere('phone_number', 'like', $term));
            });
        }

        return $query;
    }

    public function render()
    {
        $payments = $this->filteredQuery()->paginate(10);

        $tenants = StaffScope::onTenant(Tenant::query())->orderBy('tenant_name')->get();

        $invoiceOptions = collect();
        if ($this->tenant_id) {
            $invoiceOptions = Invoice::where('tenant_id', $this->tenant_id)
                ->where('status', '!=', 'paid')
                ->orderByDesc('invoice_date')
                ->get();
        }

        // Stats reflect the full scoped payment set, not just the current search/page.
        $statsQuery = StaffScope::onTenantChild(Payment::query());
        $collectedThisMonth = (float) (clone $statsQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid');
        $paymentCount = (clone $statsQuery)->count();
        $averagePayment = $paymentCount > 0 ? (clone $statsQuery)->avg('amount_paid') : 0;

        $trendLabels = [];
        $trendValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $trendLabels[] = $month->format('M');
            $trendValues[] = (float) (clone $statsQuery)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('amount_paid');
        }

        return view('livewire.admin-app.payments', [
            'payments' => $payments,
            'tenants' => $tenants,
            'invoiceOptions' => $invoiceOptions,
            'collectedThisMonth' => $collectedThisMonth,
            'paymentCount' => $paymentCount,
            'averagePayment' => (float) $averagePayment,
            'trendLabels' => $trendLabels,
            'trendValues' => $trendValues,
        ])->layout('components.layouts.app', ['title' => 'Payments']);
    }
}
