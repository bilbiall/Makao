<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class TenantDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-s-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = '';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    // This determines which Blade view to render
    protected static string $view = 'filament.tenant.pages.tenant-dashboard';

    

    // Public properties to pass data to the view
    public $houseName;
    public $pendingAmount;
    public $recentInvoices;
    public $recentPayments;

    // This runs when the page loads
    public function mount(): void
    {
        // Get the currently logged-in tenant's model
        $tenant = Auth::user()->tenant;

        // House name (if assigned)
        $this->houseName = $tenant?->house?->house_name ?? 'No House Assigned';

        // Total invoiced vs total paid (balance = pending)
        $totalInvoiced = Invoice::where('tenant_id', $tenant->id)->sum('amount');
        $totalPaid     = Payment::where('tenant_id', $tenant->id)->sum('amount_paid');

        $this->pendingAmount = max(0, $totalInvoiced - $totalPaid);

        // Get a list of the most recent invoices
        $this->recentInvoices = Invoice::where('tenant_id', $tenant->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                // Get the latest payment balance for each invoice
                $latestPayment = Payment::where('invoice_id', $invoice->id)
                    ->latest()
                    ->first();
                
                // Store the payment balance (or invoice balance if no payments)
                $invoice->payment_balance = $latestPayment?->balance ?? $invoice->balance;
                return $invoice;
            });

        // Get a list of the most recent payments
        $this->recentPayments = Payment::where('tenant_id', $tenant->id)
            ->latest()
            ->take(5)
            ->get();
    }
}

