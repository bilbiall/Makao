<?php

namespace App\Filament\Superadmin\Pages;

use App\Models\Landlord;
use App\Models\Subscription;
use Filament\Pages\Page;

class SuperadminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Overview';

    protected static string $view = 'filament.superadmin.pages.superadmin-dashboard';

    public int $totalLandlords;
    public int $trialingCount;
    public int $activeCount;
    public int $expiringSoonCount;
    public float $roughMrr;

    public function mount(): void
    {
        $this->totalLandlords = Landlord::count();
        $this->trialingCount = Subscription::where('status', 'trialing')->count();
        $this->activeCount = Subscription::where('status', 'active')->count();
        $this->expiringSoonCount = Subscription::whereIn('status', ['trialing', 'active'])
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->count();

        // Rough MRR: sum of active subscriptions' package price, normalized to monthly.
        $this->roughMrr = Subscription::where('status', 'active')
            ->with('package')
            ->get()
            ->sum(function (Subscription $subscription) {
                $price = (float) ($subscription->package->price ?? 0);

                return $subscription->package?->billing_interval === 'yearly' ? $price / 12 : $price;
            });
    }
}
