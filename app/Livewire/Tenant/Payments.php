<?php

namespace App\Livewire\Tenant;

use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination;

    public function render()
    {
        $tenant = Auth::user()->tenant;

        $payments = $tenant
            ? Payment::where('tenant_id', $tenant->id)->latest()->paginate(10)
            : Payment::whereRaw('1 = 0')->paginate(10);

        return view('livewire.tenant.payments', ['payments' => $payments])
            ->layout('components.layouts.app', ['title' => 'Payments']);
    }
}
