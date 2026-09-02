<?php

namespace App\Livewire\UserApp;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public int $watchlistCount = 0;
    public int $pendingRequestsCount = 0;
    public $recentRequests;

    public function mount(): void
    {
        $user = Auth::user();

        $this->watchlistCount = $user->watchlist()->count();
        $this->pendingRequestsCount = $user->viewingRequests()->where('status', 'pending')->count();
        $this->recentRequests = $user->viewingRequests()->with('house')->latest()->take(3)->get();
    }

    public function render()
    {
        return view('livewire.user-app.dashboard')
            ->layout('components.layouts.app', ['title' => 'Home']);
    }
}
