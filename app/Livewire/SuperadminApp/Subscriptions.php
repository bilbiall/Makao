<?php

namespace App\Livewire\SuperadminApp;

use App\Models\Subscription;
use Livewire\Component;
use Livewire\WithPagination;

class Subscriptions extends Component
{
    use WithPagination;

    public function render()
    {
        $subscriptions = Subscription::with(['landlord', 'package'])->latest()->paginate(10);

        return view('livewire.superadmin-app.subscriptions', ['subscriptions' => $subscriptions])
            ->layout('components.layouts.app', ['title' => 'Subscriptions']);
    }
}
