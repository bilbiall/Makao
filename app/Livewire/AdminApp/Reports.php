<?php

namespace App\Livewire\AdminApp;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Reports extends Component
{
    public array $months = [];
    public array $revenues = [];
    public int $paidCount = 0;
    public int $partialCount = 0;
    public int $unpaidCount = 0;

    public function mount(): void
    {
        // Matches Reports::shouldRegisterNavigation() - caretakers don't see
        // landlord-wide reporting.
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $this->months[] = $date->format('M Y');
            $this->revenues[] = Payment::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount_paid');
        }

        $this->paidCount = Invoice::where('status', 'paid')->count();
        $this->partialCount = Invoice::where('status', 'partial')->count();
        $this->unpaidCount = Invoice::where('status', 'unpaid')->count();
    }

    public function render()
    {
        $maxRevenue = max($this->revenues) ?: 1;

        return view('livewire.admin-app.reports', ['maxRevenue' => $maxRevenue])
            ->layout('components.layouts.app', ['title' => 'Reports']);
    }
}
