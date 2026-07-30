<?php

namespace App\Livewire\Tenant;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $houseName;
    public $rentAmount;
    public $pendingAmount;
    public $recentInvoices;
    public $recentPayments;
    public $paymentModeIsAutomatic = false;

    public function mount(): void
    {
        $landlordId = Auth::user()->landlord_id;
        $payload = \App\Models\Setting::forLandlord($landlordId)->payload ?? [];
        $this->paymentModeIsAutomatic = ($payload['payment_mode'] ?? 'manual') === 'automatic';

        $tenant = Auth::user()->tenant;

        $this->houseName = $tenant?->house?->house_name ?? 'No house assigned';
        $this->rentAmount = $tenant?->house?->rent_amount ?? 0;

        if (!$tenant) {
            $this->pendingAmount = 0;
            $this->recentInvoices = collect();
            $this->recentPayments = collect();
            return;
        }

        $totalInvoiced = Invoice::where('tenant_id', $tenant->id)->sum('amount');
        $totalPaid = Payment::where('tenant_id', $tenant->id)->sum('amount_paid');
        $this->pendingAmount = max(0, $totalInvoiced - $totalPaid);

        $this->recentInvoices = Invoice::where('tenant_id', $tenant->id)->latest()->take(5)->get();
        $this->recentPayments = Payment::where('tenant_id', $tenant->id)->latest()->take(5)->get();
    }

    public function render()
    {
        return view('livewire.tenant.dashboard')
            ->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
