<?php

namespace App\Livewire\UserApp;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Applications extends Component
{
    use WithPagination;

    public function render()
    {
        $requests = Auth::user()->viewingRequests()
            ->with('house.location')
            ->latest()
            ->paginate(10);

        return view('livewire.user-app.applications', ['requests' => $requests])
            ->layout('components.layouts.app', ['title' => 'Applications']);
    }
}
