<?php

namespace App\Livewire\AdminApp;

use App\Models\Bill;
use App\Models\Tenant;
use App\Support\StaffScope;
use Livewire\Component;
use Livewire\WithPagination;

class Bills extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public $tenant_id = '';
    public string $bill_month = '';
    public $water = 0;
    public $electricity = 0;
    public $internet = 0;
    public $trash = 0;
    public string $note = '';

    public function mount(): void
    {
        $this->bill_month = now()->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'bill_month' => 'required|date',
            'water' => 'nullable|numeric|min:0',
            'electricity' => 'nullable|numeric|min:0',
            'internet' => 'nullable|numeric|min:0',
            'trash' => 'nullable|numeric|min:0',
        ];
    }

    public function record(): void
    {
        $this->validate();

        Bill::create([
            'tenant_id' => $this->tenant_id,
            'bill_month' => $this->bill_month,
            'water' => $this->water ?: 0,
            'electricity' => $this->electricity ?: 0,
            'internet' => $this->internet ?: 0,
            'trash' => $this->trash ?: 0,
            'note' => $this->note,
        ]);

        $this->reset(['tenant_id', 'water', 'electricity', 'internet', 'trash', 'note', 'showForm']);
        $this->bill_month = now()->format('Y-m-d');
        session()->flash('bill-recorded', 'Bill recorded successfully.');
    }

    public function render()
    {
        $bills = StaffScope::onTenantChild(Bill::query())
            ->with('tenant')
            ->latest('bill_month')
            ->paginate(10);

        $tenants = StaffScope::onTenant(Tenant::query())->orderBy('tenant_name')->get();

        return view('livewire.admin-app.bills', ['bills' => $bills, 'tenants' => $tenants])
            ->layout('components.layouts.app', ['title' => 'Bills']);
    }
}
