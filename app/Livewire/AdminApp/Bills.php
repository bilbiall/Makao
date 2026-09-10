<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Bill;
use App\Models\BillType;
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
    public string $note = '';

    /** Each entry: ['bill_type_id' => string, 'amount' => string] - see addBillLine()/removeBillLine(). */
    public array $bill_lines = [];

    public bool $showQuickAddType = false;
    public string $new_type_name = '';
    public $new_type_default_amount = '';
    public bool $new_type_recurring = false;

    public string $monthFilter = '';
    public string $locationFilter = '';

    public function mount(): void
    {
        $this->bill_month = now()->format('Y-m-d');
        $this->bill_lines = [$this->emptyLine()];
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
            'bill_lines' => 'array',
            'bill_lines.*.bill_type_id' => 'required|exists:bill_types,id',
            'bill_lines.*.amount' => 'required|numeric|min:0',
        ];
    }

    private function emptyLine(): array
    {
        return ['bill_type_id' => '', 'amount' => ''];
    }

    public function addBillLine(): void
    {
        $this->bill_lines[] = $this->emptyLine();
    }

    public function removeBillLine(int $index): void
    {
        unset($this->bill_lines[$index]);
        $this->bill_lines = array_values($this->bill_lines);

        if (empty($this->bill_lines)) {
            $this->bill_lines = [$this->emptyLine()];
        }
    }

    /** Prefills a line's amount from the selected type's default_amount, if it has one and the line is still blank. */
    public function updatedBillLines($value, $key): void
    {
        if (!str_ends_with($key, '.bill_type_id')) {
            return;
        }

        $index = explode('.', $key)[0];
        $type = $value ? BillType::find($value) : null;

        if ($type && $type->default_amount !== null
            && (($this->bill_lines[$index]['amount'] ?? '') === '')) {
            $this->bill_lines[$index]['amount'] = (string) $type->default_amount;
        }
    }

    /**
     * Lets a PM define a new charge type without leaving the Bills page - full
     * management (property scoping, recurring, deactivating) lives on the Bill Types
     * page. Same MANAGE_BILL_TYPES permission gates both surfaces.
     */
    public function quickAddBillType(): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::MANAGE_BILL_TYPES), 403);

        $this->validate([
            'new_type_name' => 'required|string|max:255',
            'new_type_default_amount' => 'nullable|numeric|min:0',
        ]);

        $type = BillType::create([
            'landlord_id' => Auth::user()->landlord_id,
            'name' => $this->new_type_name,
            'default_amount' => $this->new_type_default_amount !== '' ? $this->new_type_default_amount : null,
            'is_recurring' => $this->new_type_recurring,
            'is_active' => true,
        ]);

        // Immediately usable - append a fresh line pre-selecting the type just made.
        $this->bill_lines[] = [
            'bill_type_id' => (string) $type->id,
            'amount' => $type->default_amount !== null ? (string) $type->default_amount : '',
        ];

        $this->reset(['new_type_name', 'new_type_default_amount', 'new_type_recurring', 'showQuickAddType']);
        session()->flash('bill-recorded', "Bill type \"{$type->name}\" added.");
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'tenant_id', 'note']);
        $this->bill_month = now()->format('Y-m-d');
        $this->bill_lines = [$this->emptyLine()];
        $this->showForm = true;
    }

    public function startEdit(int $billId): void
    {
        $bill = StaffScope::onTenantChild(Bill::query())->with('items')->findOrFail($billId);

        $this->editingId = $bill->id;
        $this->tenant_id = $bill->tenant_id;
        $this->bill_month = \Carbon\Carbon::parse($bill->bill_month)->format('Y-m-d');
        $this->note = $bill->note ?? '';
        $this->bill_lines = $bill->items->map(fn ($item) => [
            'bill_type_id' => (string) $item->bill_type_id,
            'amount' => (string) $item->amount,
        ])->all();

        if (empty($this->bill_lines)) {
            $this->bill_lines = [$this->emptyLine()];
        }

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
            'note' => $this->note,
        ];

        if ($this->editingId) {
            $bill = StaffScope::onTenantChild(Bill::query())->findOrFail($this->editingId);
            $bill->update($data);

            // Wholesale replace, same convention as HouseResource::EditHouse's BnB
            // pricing tiers - simpler than diffing which lines changed.
            $bill->items()->delete();
            foreach ($this->bill_lines as $line) {
                $bill->items()->create(['bill_type_id' => $line['bill_type_id'], 'amount' => $line['amount']]);
            }

            session()->flash('bill-recorded', 'Bill updated successfully.');
        } else {
            $bill = Bill::create($data);
            foreach ($this->bill_lines as $line) {
                $bill->items()->create(['bill_type_id' => $line['bill_type_id'], 'amount' => $line['amount']]);
            }

            $bill->logAndNotifyRecorded();
            session()->flash('bill-recorded', 'Bill recorded successfully.');
        }

        $this->reset(['editingId', 'tenant_id', 'note', 'showForm']);
        $this->bill_month = now()->format('Y-m-d');
        $this->bill_lines = [$this->emptyLine()];
    }

    public function delete(int $billId): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::DELETE_BILLS), 403);

        StaffScope::onTenantChild(Bill::query())->findOrFail($billId)->delete();
        session()->flash('bill-recorded', 'Bill deleted.');
    }

    protected function filteredQuery()
    {
        $query = StaffScope::onTenantChild(Bill::query())
            ->with(['tenant.house.location', 'items.billType'])
            ->latest('bill_month');

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

        $rows = [];
        foreach ($bills as $bill) {
            if ($bill->items->isEmpty()) {
                $rows[] = [$bill->tenant?->tenant_name, \Carbon\Carbon::parse($bill->bill_month)->format('Y-m'), '—', 0, $bill->note];
                continue;
            }

            foreach ($bill->items as $item) {
                $rows[] = [
                    $bill->tenant?->tenant_name,
                    \Carbon\Carbon::parse($bill->bill_month)->format('Y-m'),
                    $item->billType?->name ?? 'Deleted type',
                    $item->amount,
                    $bill->note,
                ];
            }
        }

        return $this->streamCsv(
            'bills.csv',
            ['Tenant', 'Bill Month', 'Bill Type', 'Amount', 'Note'],
            $rows
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

        // BillType is landlord-scoped automatically (BelongsToLandlord global scope),
        // so this already only returns the current landlord's own types.
        $billTypes = BillType::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin-app.bills', [
            'bills' => $bills,
            'tenants' => $tenants,
            'locations' => $locations,
            'billTypes' => $billTypes,
            'canManageBillTypes' => Auth::user()->hasPermission(StaffPermissions::MANAGE_BILL_TYPES),
        ])->layout('components.layouts.app', ['title' => 'Bills']);
    }
}
