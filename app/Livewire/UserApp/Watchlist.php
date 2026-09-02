<?php

namespace App\Livewire\UserApp;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Watchlist extends Component
{
    use WithPagination;

    public function unwatch(int $houseId): void
    {
        Auth::user()->watchlist()->detach($houseId);
    }

    public function render()
    {
        $houses = Auth::user()->watchlist()->with('location', 'photos')->paginate(10);

        return view('livewire.user-app.watchlist', ['houses' => $houses])
            ->layout('components.layouts.app', ['title' => 'Watchlist']);
    }
}
