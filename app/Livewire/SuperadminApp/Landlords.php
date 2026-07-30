<?php

namespace App\Livewire\SuperadminApp;

use App\Models\Landlord;
use Livewire\Component;
use Livewire\WithPagination;

class Landlords extends Component
{
    use WithPagination;

    public function render()
    {
        $landlords = Landlord::with('currentSubscription.package')->latest()->paginate(10);

        return view('livewire.superadmin-app.landlords', ['landlords' => $landlords])
            ->layout('components.layouts.app', ['title' => 'Landlords']);
    }
}
