<?php

namespace App\Livewire\AdminApp;

use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Properties extends Component
{
    public function render()
    {
        $user = Auth::user();
        $query = Location::with('houses');

        // Caretakers are narrowed to their single assigned location - same rule
        // enforced today in HouseResource::getEloquentQuery().
        if ($user->role === 'caretaker' && $user->location_id) {
            $query->where('id', $user->location_id);
        }

        $locations = $query->get();

        return view('livewire.admin-app.properties', ['locations' => $locations])
            ->layout('components.layouts.app', ['title' => 'Properties']);
    }
}
