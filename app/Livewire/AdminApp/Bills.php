<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Bill;
use App\Models\Location;
use App\Models\Tenant;
use App\Support\StaffPermissions;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Bills extends Component
{
    use WithPagination;
    use ExportsCsv;

    public bool $showForm = false;
    public ?int $editingId = null;
    public $tenant_id = '';
    public string $bill_month = '';
    public $water = 0;
    public $electricity = 0;
    public $internet = 0;
    public $trash = 0;
    public string $note = '';

    public string $monthFilter = '';
    public string $locationFilter = '';

    public function mount(): void
    {
        $this->bill_month = now()->format('Y-m-d');
    }

    public function updatingMonthFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
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

    public function startCreate(): void
    {
        $this->reset(['editingId', 'tenant_id', 'water', 'electricity', 'internet', 'trash', 'note']);
        $this->bill_month = now()->format('Y-m-d');
        $this->showForm = true;
    }

    public function startEdit(int $billId): void
    {
        $bill = StaffScope::onTenantChild(Bill::query())->findOrFail($billId);

        $this->editingId = $bill->id;
        $this->tenant_id = $bill->tenant_id;
        $this->bill_month = \Carbon\Carbon::parse($bill->bill_month)->format('Y-m-d');
        $this->water = $bill->water;
        $this->electricity = $bill->electricity;
        $this->internet = $bill->internet;
        $this->trash = $bill->trash;
        $this->note = $bill->note ?? '';
        $this->showForm = true;
    }

    public function record(): void
    {
        $slug = $this->editingId ? StaffPermissions::EDIT_BILLS : StaffPermissions::CREATE_BILLS;
        abort_unless(Auth::user()->hasPermission($slug), 403);

        $this->validate();

        $data = [
            'tenant_id' => $this->tenant_id,
            'bill_month' => $this->bill_month,
            'water' => $this->water ?: 0,
            'electricity' => $this->electricity ?: 0,
            'internet' => $this->internet ?: 0,
            'trash' => $this->trash ?: 0,
            'note' => $this->note,
        ];

        if ($this->editingId) {
            StaffScope::onTenantChild(Bill::query())->findOrFail($this->editingId)->update($data);
            session()->flash('bill-recorded', 'Bill updated successfully.');
        } else {
            Bill::create($data);
            session()->flash('bill-recorded', 'Bill recorded successfully.');
        }

        $this->reset(['editingId', 'tenant_id', 'water', 'electricity', 'internet', 'trash', 'note', 'showForm']);
        $this->bill_month = now()->format('Y-m-d');
    }

    public function delete(int $billId): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::DELETE_BILLS), 403);

        StaffScope::onTenantChild(Bill::query())->findOrFail($billId)->delete();
        session()->flash('bill-recorded', 'Bill deleted.');
    }

    protected function filteredQuery()
    {
        $query = StaffScope::onTenantChild(Bill::query())->with('tenant.house.location')->latest('bill_month');

        if ($this->monthFilter) {
            $date = \Carbon\Carbon::parse($this->monthFilter);
            $query->whereMonth('bill_month', $date->month)->whereYear('bill_month', $date->year);
        }

        if ($this->locationFilter) {
            $query->whereHas('tenant.house.location', fn ($q) => $q->where('id', $this->locationFilter));
        }

        return $query;
    }

    public function export()
    {
        $bills = $this->filteredQuery()->get();

        return $this->streamCsv(
            'bills.csv',
            ['Tenant', 'Bill Month', 'Electricity', 'Water', 'Trash', 'Internet', 'Total', 'Note'],
            $bills->map(fn (Bill $bill) => [
                $bill->tenant?->tenant_name,
                \Carbon\Carbon::parse($bill->bill_month)->format('Y-m'),
                $bill->electricity,
                $bill->water,
                $bill->trash,
                $bill->internet,
                $bill->water + $bill->electricity + $bill->internet + $bill->trash,
                $bill->note,
            ])
        );
    }

    public function render()
    {
        $bills = $this->filteredQuery()->paginate(10);

        $tenants = StaffScope::onTenant(Tenant::query())->orderBy('tenant_name')->get();

        // Location has no location_id column of its own (it IS the location) -
        // StaffScope::onHouse() doesn't fit here, so scope directly by id.
        $locationsQuery = Location::query()->orderBy('location_name');
        if (StaffScope::isScopedStaff()) {
            $locationsQuery->whereIn('id', StaffScope::locationIds());
        } elseif (StaffScope::isAgent()) {
            $locationsQuery->whereRaw('1 = 0');
        }
        $locations = $locationsQuery->get();

        return view('livewire.admin-app.bills', ['bills' => $bills, 'tenants' => $tenants, 'locations' => $locations])
            ->layout('components.layouts.app', ['title' => 'Bills']);
    }
}
