<?php

namespace App\Livewire\SuperadminApp;

use App\Models\Package;
use Livewire\Component;

class Packages extends Component
{
    public function render()
    {
        $packages = Package::orderBy('sort_order')->get();

        return view('livewire.superadmin-app.packages', ['packages' => $packages])
            ->layout('components.layouts.app', ['title' => 'Packages']);
    }
}
