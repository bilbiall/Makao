<?php

namespace App\Livewire\AdminApp;

use App\Models\Invoice;
use App\Support\StaffScope;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = StaffScope::onTenantChild(Invoice::query())->with('tenant')->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin-app.invoices', ['invoices' => $query->paginate(10)])
            ->layout('components.layouts.app', ['title' => 'Invoices']);
    }
}
