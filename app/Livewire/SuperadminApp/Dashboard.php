<?php

namespace App\Livewire\SuperadminApp;

use App\Models\Landlord;
use App\Models\Subscription;
use Livewire\Component;

class Dashboard extends Component
{
    public int $totalLandlords;
    public int $trialingCount;
    public int $activeCount;
    public $mrr;

    public function mount(): void
    {
        $this->totalLandlords = Landlord::count();
        $this->trialingCount = Subscription::where('status', 'trialing')->count();
        $this->activeCount = Subscription::where('status', 'active')->count();
        $this->mrr = Subscription::where('status', 'active')
            ->with('package')
            ->get()
            ->sum(fn ($s) => $s->package?->price ?? 0);
    }

    public function render()
    {
        return view('livewire.superadmin-app.dashboard')
            ->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
