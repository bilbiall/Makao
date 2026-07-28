<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Carbon\Carbon;

use App\Models\Tenant;
use App\Models\House;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ActivityLog;
use App\Models\Location;


class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-s-home';

    protected static ?string $title = 'Dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    protected static string $view = 'filament.pages.dashboard';

    //dashboard view
    public $location_id;
    public $locations;
    public $totalTenants;
    public $newTenants;
    public $totalHouses;
    public $vacantHouses;
    public $occupiedHouses;
    public $totalInvoices;
    public $paidInvoices;
    public $unpaidInvoices;
    public $partialInvoices;
    public $totalPayments;
    public $totalRevenue;
    public $recentPayments;
    public $occupancyRate;
    public $monthlyRevenueData;
    public $invoiceStatusData;
    public $activityTrendData;
    public $newTenantsThisMonth;
    public $outstandingBalance;

    public function mount(): void
    {
        // Load all locations for filter
        $this->locations = Location::all();

        $this->loadDashboardData();
    }

    public function updatedLocationId()
    {
        $this->loadDashboardData();
    }

    private function loadDashboardData()
    {
        // Apply location filter to tenants query
        $tenantsQuery = Tenant::query();
        if ($this->location_id) {
            $tenantsQuery->whereHas('house', function ($query) {
                $query->where('location_id', $this->location_id);
            });
        }

        // Apply location filter to houses query
        $housesQuery = House::query();
        if ($this->location_id) {
            $housesQuery->where('location_id', $this->location_id);
        }

        // Apply location filter to invoices query
        $invoicesQuery = Invoice::query();
        if ($this->location_id) {
            $invoicesQuery->whereHas('tenant.house', function ($query) {
                $query->where('location_id', $this->location_id);
            });
        }

        // Apply location filter to payments query
        $paymentsQuery = Payment::query();
        if ($this->location_id) {
            $paymentsQuery->whereHas('tenant.house', function ($query) {
                $query->where('location_id', $this->location_id);
            });
        }

        // Total & New Tenants
        $this->totalTenants = $tenantsQuery->count();
        $this->newTenants = (clone $tenantsQuery)->whereMonth('created_at', now()->month)->count();
        $this->newTenantsThisMonth = (clone $tenantsQuery)->whereDate('created_at', '>=', now()->startOfMonth())->count();

        // House Stats
        $this->totalHouses = $housesQuery->count();
        $this->vacantHouses = (clone $housesQuery)->where('house_status', 'Vacant')->count();
        $this->occupiedHouses = (clone $housesQuery)->where('house_status', 'Occupied')->count();
        $this->occupancyRate = $this->totalHouses > 0 ? round(($this->occupiedHouses / $this->totalHouses) * 100) : 0;

        // Invoice Stats
        $this->totalInvoices = $invoicesQuery->count();
        $this->paidInvoices = (clone $invoicesQuery)->where('status', 'paid')->count();
        $this->unpaidInvoices = (clone $invoicesQuery)->where('status', 'unpaid')->count();
        $this->partialInvoices = (clone $invoicesQuery)->where('status', 'partial')->count();

        // Outstanding Balance
        $this->outstandingBalance = (clone $invoicesQuery)->where('status', '!=', 'paid')
            ->sum('balance');

        // Payment Stats
        $this->totalPayments = (clone $paymentsQuery)->whereMonth('created_at', now()->month)->sum('amount_paid');
        $this->totalRevenue = $paymentsQuery->sum('amount_paid');
        $this->recentPayments = (clone $paymentsQuery)->latest()->take(8)->get();

        // Charts Data
        $this->monthlyRevenueData = $this->getMonthlyRevenueData();
        $this->invoiceStatusData = $this->getInvoiceStatusData();
        $this->activityTrendData = $this->getActivityTrendData();
    }

    private function getMonthlyRevenueData()
    {
        $months = [];
        $revenues = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $query = Payment::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year);
            
            if ($this->location_id) {
                $query->whereHas('tenant.house', function ($q) {
                    $q->where('location_id', $this->location_id);
                });
            }
            
            $revenues[] = $query->sum('amount_paid');
        }

        return [
            'months' => $months,
            'revenues' => $revenues,
        ];
    }

    private function getInvoiceStatusData()
    {
        return [
            'paid' => $this->paidInvoices,
            'unpaid' => $this->unpaidInvoices,
            'partial' => $this->partialInvoices,
        ];
    }

    private function getActivityTrendData()
    {
        $days = [];
        $activities = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days[] = $date->format('D');
            $activities[] = ActivityLog::whereDate('created_at', $date)->count();
        }

        return [
            'days' => $days,
            'activities' => $activities,
        ];
    }

}
