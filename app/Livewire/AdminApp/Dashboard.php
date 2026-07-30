<?php

namespace App\Livewire\AdminApp;

use App\Models\Bill;
use App\Models\House;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Support\CaretakerScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $totalTenants;
    public $totalHouses;
    public $occupiedHouses;
    public $occupancyRate;
    public $revenueThisMonth;
    public $outstandingBalance;
    public $recentPayments;

    public function mount(): void
    {
        $tenants = CaretakerScope::onTenant(Tenant::query());
        $this->totalTenants = $tenants->count();

        $houses = CaretakerScope::onHouse(House::query());
        $this->totalHouses = (clone $houses)->count();
        $this->occupiedHouses = (clone $houses)->where('house_status', 'Occupied')->count();
        $this->occupancyRate = $this->totalHouses > 0 ? round(($this->occupiedHouses / $this->totalHouses) * 100) : 0;

        $this->revenueThisMonth = CaretakerScope::onTenantChild(Payment::query())
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid');

        $this->outstandingBalance = CaretakerScope::onTenantChild(Invoice::query())
            ->where('status', '!=', 'paid')
            ->sum('balance');

        $this->recentPayments = CaretakerScope::onTenantChild(Payment::query())
            ->with('tenant')
            ->latest()
            ->take(6)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin-app.dashboard')
            ->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
