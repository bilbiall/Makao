<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Invoice;
use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\StaffScope;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination;
    use ExportsCsv;

    public bool $showForm = false;
    public $tenant_id = '';
    public $invoice_id = '';
    public $amount_paid = '';
    public string $payment_reference = '';
    public string $payment_method = 'cash';
    public string $payment_date = '';

    public string $search = '';
    public string $monthFilter = '';
    public string $typeFilter = '';

    // Which payment's detail popup is open, if any.
    public ?int $selectedPaymentId = null;

    // Editing an existing payment.
    public ?int $editingId = null;
    public $edit_amount_paid = '';
    public string $edit_payment_reference = '';
    public string $edit_payment_method = 'cash';
    public string $edit_payment_date = '';
    public string $edit_mpesa_transaction_id = '';

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMonthFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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

        if ($this->monthFilter) {
            $date = \Carbon\Carbon::parse($this->monthFilter . '-01');
            $query->whereMonth('payment_date', $date->month)->whereYear('payment_date', $date->year);
        }

        if ($this->typeFilter) {
            $query->where('payment_type', $this->typeFilter);
        }

        return $query;
    }

    public function startEdit(int $paymentId): void
    {
        $payment = StaffScope::onTenantChild(Payment::query())->findOrFail($paymentId);

        $this->editingId = $payment->id;
        $this->edit_amount_paid = $payment->amount_paid;
        $this->edit_payment_reference = $payment->payment_reference ?? '';
        $this->edit_payment_method = $payment->payment_method ?? 'cash';
        $this->edit_payment_date = $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : now()->format('Y-m-d');
        $this->edit_mpesa_transaction_id = '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'edit_amount_paid', 'edit_payment_reference', 'edit_payment_method', 'edit_payment_date', 'edit_mpesa_transaction_id']);
    }

    public function updatedEditMpesaTransactionId(): void
    {
        if ($this->edit_mpesa_transaction_id && $transaction = MpesaTransaction::find($this->edit_mpesa_transaction_id)) {
            $this->edit_payment_reference = $transaction->reference;
        }
    }

    public function update(): void
    {
        $this->validate([
            'edit_amount_paid' => 'required|numeric|min:1',
            'edit_payment_reference' => 'required|string|max:255',
            'edit_payment_method' => 'required|string',
            'edit_payment_date' => 'required|date',
        ]);

        $payment = StaffScope::onTenantChild(Payment::query())->findOrFail($this->editingId);

        // Payment::booted()'s `updated` listener recalculates the invoice's
        // balance/status and the tenant's running balance whenever
        // amount_paid actually changes - no manual recompute needed here.
        $payment->update([
            'amount_paid' => $this->edit_amount_paid,
            'payment_reference' => $this->edit_payment_reference,
            'payment_method' => $this->edit_payment_method,
            'payment_date' => $this->edit_payment_date,
        ]);

        $this->cancelEdit();
        session()->flash('payment-recorded', 'Payment updated.');
    }

    public function delete(int $paymentId): void
    {
        StaffScope::onTenantChild(Payment::query())->findOrFail($paymentId)->delete();

        session()->flash('payment-recorded', 'Payment deleted.');
    }

    public function getUnmatchedMpesaOptionsProperty()
    {
        return MpesaTransaction::whereIn('status', ['completed', 'success'])
            ->whereDoesntHave('payment', fn ($q) => $q->whereNotNull('id'))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function export()
    {
        $payments = $this->filteredQuery()->get();

        return $this->streamCsv(
            'payments.csv',
            ['Tenant', 'Reference', 'Invoice', 'Amount Paid', 'Type', 'Balance', 'Payment Date'],
            $payments->map(fn (Payment $payment) => [
                $payment->tenant?->tenant_name,
                $payment->payment_reference,
                $payment->invoice?->invoice_number,
                $payment->amount_paid,
                $payment->payment_type,
                $payment->balance,
                $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : '',
            ])
        );
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
