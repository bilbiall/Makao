<?php

namespace App\Livewire\AdminApp;

use App\Models\Bill;
use App\Models\Booking;
use App\Models\House;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\ViewingRequest;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $totalTenants;
    public $totalHouses;
    public $occupiedHouses;
    public $occupancyRate;
    public $revenueThisMonth;
    public $revenueLastMonth;
    public $revenueChangePercent;
    public $outstandingBalance;
    public $recentPayments;

    public $revenueTrendLabels = [];
    public $revenueTrendValues = [];

    public $upcomingBookingsCount;
    public $pendingBookingsCount;
    public $pendingViewingRequestsCount;

    public $invoiceStatusLabels = [];
    public $invoiceStatusValues = [];
    public $invoiceStatusColors = [];

    public function mount(): void
    {
        // Agent manages bookings for specific short_term houses, not tenants/invoices/
        // payments - none of this dashboard's data applies to that role, and StaffScope's
        // caretaker/manager-only helpers would otherwise fall through unscoped for it.
        if (Auth::user()->role === 'agent') {
            redirect()->route('app.admin.bookings');
            return;
        }

        $tenants = StaffScope::onTenant(Tenant::query());
        $this->totalTenants = $tenants->count();

        $houses = StaffScope::onHouse(House::query());
        $this->totalHouses = (clone $houses)->count();
        $this->occupiedHouses = (clone $houses)->where('house_status', 'Occupied')->count();
        $this->occupancyRate = $this->totalHouses > 0 ? round(($this->occupiedHouses / $this->totalHouses) * 100) : 0;

        $this->revenueThisMonth = StaffScope::onTenantChild(Payment::query())
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid');

        $this->revenueLastMonth = StaffScope::onTenantChild(Payment::query())
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount_paid');

        $this->revenueChangePercent = $this->revenueLastMonth > 0
            ? round((($this->revenueThisMonth - $this->revenueLastMonth) / $this->revenueLastMonth) * 100)
            : ($this->revenueThisMonth > 0 ? 100 : 0);

        $this->outstandingBalance = StaffScope::onTenantChild(Invoice::query())
            ->where('status', '!=', 'paid')
            ->sum('balance');

        $this->recentPayments = StaffScope::onTenantChild(Payment::query())
            ->with('tenant')
            ->latest()
            ->take(6)
            ->get();

        // 6-month revenue trend, oldest to newest - same "at a glance" sparkline idea
        // already used on the marketing homepage's dashboard preview, but built from
        // this landlord's real payment history instead of illustrative numbers.
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $this->revenueTrendLabels[] = $month->format('M');
            $this->revenueTrendValues[] = (float) StaffScope::onTenantChild(Payment::query())
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('amount_paid');
        }

        // Invoice status breakdown (by invoiced amount, not count) - a second, genuinely
        // different lens on collections health alongside the plain "Outstanding" total.
        // Uses `amount` (the invoiced total) rather than `balance`, since a fully paid
        // invoice's balance is 0 by definition and would otherwise always vanish here.
        $invoicesByStatus = StaffScope::onTenantChild(Invoice::query())
            ->selectRaw('status, SUM(amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusMeta = [
            'paid' => ['label' => 'Paid', 'color' => '#059669'],
            'partial' => ['label' => 'Partial', 'color' => '#d97706'],
            'unpaid' => ['label' => 'Unpaid', 'color' => '#e11d48'],
        ];
        foreach ($statusMeta as $status => $meta) {
            $amount = (float) ($invoicesByStatus[$status] ?? 0);
            if ($amount <= 0) {
                continue;
            }
            $this->invoiceStatusLabels[] = $meta['label'];
            $this->invoiceStatusValues[] = $amount;
            $this->invoiceStatusColors[] = $meta['color'];
        }

        // Short-stay activity - only meaningful for landlords/admins who actually run
        // short_term units; StaffScope::onHouseOrAssignedHouse covers Manager/Caretaker
        // (whole property) the same way onHouse does above, so this stays consistent.
        $bookings = StaffScope::onHouseOrAssignedHouse(Booking::query());
        $this->upcomingBookingsCount = (clone $bookings)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_in', '>=', now()->toDateString())
            ->count();
        $this->pendingBookingsCount = (clone $bookings)->where('status', 'pending')->count();

        $this->pendingViewingRequestsCount = StaffScope::onTenant(ViewingRequest::query())
            ->where('status', 'pending')
            ->count();
    }

    public function render()
    {
        return view('livewire.admin-app.dashboard')
            ->layout('components.layouts.app', ['title' => 'Dashboard', 'hideHeading' => true]);
    }
}
