<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\MpesaC2bTransaction;
use App\Models\Payment;
use App\Models\PendingPayment;
use App\Models\Tenant;
use App\Services\MpesaC2bMatchService;
use App\Support\StaffPermissions;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * App-shell equivalent of MpesaC2bTransactionResource + PendingPaymentResource -
 * both had zero AdminApp presence before this, leaving the "I paid but it's not
 * on my invoice" resolution workflow reachable only from the Filament panel.
 */
class MpesaReview extends Component
{
    use WithPagination;
    use ExportsCsv;

    public string $tab = 'c2b';

    public string $c2bStatusFilter = 'needs_review';

    public string $pendingStatusFilter = '';

    // Which C2B row is currently showing its "assign to tenant" picker.
    public ?int $assigningTransactionId = null;

    public string $assigningTenantId = '';

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage('c2bPage');
        $this->resetPage('pendingPage');
    }

    public function updatingC2bStatusFilter(): void
    {
        $this->resetPage('c2bPage');
    }

    public function updatingPendingStatusFilter(): void
    {
        $this->resetPage('pendingPage');
    }

    public function startAssign(int $transactionId): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::RESOLVE_MPESA_REVIEW), 403);

        $this->assigningTransactionId = $transactionId;
        $this->assigningTenantId = '';
    }

    public function cancelAssign(): void
    {
        $this->assigningTransactionId = null;
        $this->assigningTenantId = '';
    }

    public function candidateTenants(MpesaC2bTransaction $transaction)
    {
        return Tenant::withoutGlobalScopes()
            ->where('landlord_id', $transaction->landlord_id)
            ->when($transaction->location_id, fn ($q) => $q->whereHas('house', fn ($h) => $h->where('location_id', $transaction->location_id)))
            ->with('house')
            ->get();
    }

    public function assignToTenant(int $transactionId, int $tenantId): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::RESOLVE_MPESA_REVIEW), 403);

        $transaction = $this->c2bBaseQuery()->findOrFail($transactionId);
        $tenant = Tenant::withoutGlobalScopes()->find($tenantId);

        if (!$tenant) {
            session()->flash('mpesa-error', 'Tenant not found.');
            $this->cancelAssign();

            return;
        }

        app(MpesaC2bMatchService::class)->manuallyMatch($transaction, $tenant);

        $this->cancelAssign();
        session()->flash('mpesa-status', "Payment assigned to {$tenant->tenant_name}.");
    }

    public function markCompleted(int $id): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::RESOLVE_MPESA_REVIEW), 403);

        $pending = StaffScope::onTenantChild(PendingPayment::query())->findOrFail($id);

        if ($pending->invoice_id) {
            Payment::create([
                'tenant_id' => $pending->tenant_id,
                'invoice_id' => $pending->invoice_id,
                'amount_paid' => $pending->amount,
                'payment_reference' => $pending->reference ?? Str::uuid()->toString(),
                'payment_date' => now(),
                'note' => 'Manually confirmed by admin (PendingPayment ID: ' . $pending->id . ')',
            ]);
        }

        $pending->status = 'completed';
        $pending->save();

        session()->flash('mpesa-status', 'Payment marked completed.');
    }

    public function markFailed(int $id): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::RESOLVE_MPESA_REVIEW), 403);

        $pending = StaffScope::onTenantChild(PendingPayment::query())->findOrFail($id);
        $pending->status = 'failed';
        $pending->save();

        session()->flash('mpesa-status', 'Payment marked failed.');
    }

    protected function c2bBaseQuery()
    {
        return StaffScope::onRelation(MpesaC2bTransaction::query(), 'tenant.house')
            ->with(['tenant.house', 'channel']);
    }

    protected function c2bQuery()
    {
        $query = $this->c2bBaseQuery()->latest('created_at');

        if ($this->c2bStatusFilter) {
            $query->where('match_status', $this->c2bStatusFilter);
        }

        return $query;
    }

    protected function pendingQuery()
    {
        $query = StaffScope::onTenantChild(PendingPayment::query())
            ->with(['tenant.house'])
            ->latest('created_at');

        if ($this->pendingStatusFilter) {
            $query->where('status', $this->pendingStatusFilter);
        }

        return $query;
    }

    public function exportC2b()
    {
        $rows = $this->c2bQuery()->get();

        return $this->streamCsv(
            'mpesa-c2b-transactions.csv',
            ['Received', 'Channel', 'Account Number', 'Tenant', 'Amount', 'Phone', 'Status'],
            $rows->map(fn (MpesaC2bTransaction $t) => [
                optional($t->created_at)->format('Y-m-d H:i'),
                $t->channel?->label ?? $t->business_shortcode,
                $t->bill_ref_number,
                $t->tenant?->tenant_name ?? 'Unmatched',
                $t->trans_amount,
                $t->msisdn,
                $t->match_status,
            ])
        );
    }

    public function exportPending()
    {
        $rows = $this->pendingQuery()->get();

        return $this->streamCsv(
            'mpesa-pending-payments.csv',
            ['Reference', 'Tenant', 'House', 'Amount', 'Status', 'Created At'],
            $rows->map(fn (PendingPayment $p) => [
                $p->reference,
                $p->tenant?->tenant_name,
                $p->tenant?->house?->house_name,
                $p->amount,
                $p->status,
                optional($p->created_at)->format('Y-m-d H:i'),
            ])
        );
    }

    public function render()
    {
        $c2bTransactions = $this->c2bQuery()->paginate(15, ['*'], 'c2bPage');
        $pendingPayments = $this->pendingQuery()->paginate(15, ['*'], 'pendingPage');

        $needsReviewCount = (clone $this->c2bBaseQuery())->where('match_status', 'needs_review')->count();
        $pendingCount = (clone StaffScope::onTenantChild(PendingPayment::query()))->where('status', 'pending')->count();

        return view('livewire.admin-app.mpesa-review', [
            'c2bTransactions' => $c2bTransactions,
            'pendingPayments' => $pendingPayments,
            'needsReviewCount' => $needsReviewCount,
            'pendingCount' => $pendingCount,
            'assigningCandidates' => $this->assigningTransactionId
                ? $this->candidateTenants($this->c2bBaseQuery()->find($this->assigningTransactionId))
                : collect(),
            'canResolveMpesaReview' => Auth::user()->hasPermission(StaffPermissions::RESOLVE_MPESA_REVIEW),
        ])->layout('components.layouts.app', ['title' => 'M-Pesa Review']);
    }
}
