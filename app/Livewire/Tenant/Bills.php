<?php

namespace App\Livewire\Tenant;

use App\Models\Bill;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Bills extends Component
{
    use WithPagination;

    public function render()
    {
        $tenant = Auth::user()->tenant;

        $bills = $tenant
            ? Bill::where('tenant_id', $tenant->id)->latest('bill_month')->paginate(10)
            : Bill::whereRaw('1 = 0')->paginate(10);

        return view('livewire.tenant.bills', ['bills' => $bills])
            ->layout('components.layouts.app', ['title' => 'Bills']);
    }
}
