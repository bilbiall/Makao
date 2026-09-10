<?php

namespace App\Livewire\AdminApp;

use App\Models\BillType;
use App\Models\Location;
use App\Support\StaffPermissions;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BillTypes extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $location_id = '';
    public $default_amount = '';
    public bool $is_recurring = false;
    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::MANAGE_BILL_TYPES), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'default_amount' => 'nullable|numeric|min:0',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'name', 'location_id', 'default_amount', 'is_recurring']);
        $this->is_active = true;
        $this->showForm = true;
    }

    public function startEdit(int $billTypeId): void
    {
        $type = BillType::where('landlord_id', Auth::user()->landlord_id)->findOrFail($billTypeId);

        $this->editingId = $type->id;
        $this->name = $type->name;
        $this->location_id = $type->location_id ? (string) $type->location_id : '';
        $this->default_amount = $type->default_amount !== null ? (string) $type->default_amount : '';
        $this->is_recurring = $type->is_recurring;
        $this->is_active = $type->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'location_id' => $this->location_id ?: null,
            'default_amount' => $this->default_amount !== '' ? $this->default_amount : null,
            'is_recurring' => $this->is_recurring,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            BillType::where('landlord_id', Auth::user()->landlord_id)->findOrFail($this->editingId)->update($data);
            session()->flash('bill-type-saved', 'Bill type updated.');
        } else {
            $data['landlord_id'] = Auth::user()->landlord_id;
            BillType::create($data);
            session()->flash('bill-type-saved', 'Bill type created.');
        }

        $this->showForm = false;
    }

    public function delete(int $billTypeId): void
    {
        $type = BillType::where('landlord_id', Auth::user()->landlord_id)->findOrFail($billTypeId);

        if ($type->items()->exists()) {
            session()->flash('bill-type-error', 'This type has bills recorded against it - deactivate it instead so past bills keep their breakdown.');
            return;
        }

        $type->delete();
        session()->flash('bill-type-saved', 'Bill type deleted.');
    }

    public function render()
    {
        $types = BillType::where('landlord_id', Auth::user()->landlord_id)
            ->withCount('items')
            ->with('location')
            ->orderBy('name')
            ->get();

        $locations = Location::where('landlord_id', Auth::user()->landlord_id)
            ->orderBy('location_name')
            ->get();

        return view('livewire.admin-app.bill-types', [
            'types' => $types,
            'locations' => $locations,
        ])->layout('components.layouts.app', ['title' => 'Bill Types']);
    }
}
