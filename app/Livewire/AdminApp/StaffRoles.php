<?php

namespace App\Livewire\AdminApp;

use App\Models\StaffRole;
use App\Support\StaffPermissions;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StaffRoles extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $scope_type = 'location';
    public array $permissions = [];

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'scope_type' => 'required|in:location,house',
            'permissions' => 'array',
            'permissions.*' => 'in:' . implode(',', StaffPermissions::all()),
        ];
    }

    public function startCreate(): void
    {
        $this->reset(['editingId', 'permissions']);
        $this->name = '';
        $this->scope_type = 'location';
        $this->showForm = true;
    }

    public function startEdit(int $staffRoleId): void
    {
        $role = StaffRole::where('landlord_id', Auth::user()->landlord_id)->findOrFail($staffRoleId);

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->scope_type = $role->scope_type;
        $this->permissions = $role->permissions ?? [];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'scope_type' => $this->scope_type,
            'permissions' => $this->permissions,
        ];

        if ($this->editingId) {
            StaffRole::where('landlord_id', Auth::user()->landlord_id)->findOrFail($this->editingId)->update($data);
            session()->flash('staff-role-saved', 'Staff role updated.');
        } else {
            $data['landlord_id'] = Auth::user()->landlord_id;
            StaffRole::create($data);
            session()->flash('staff-role-saved', 'Staff role created.');
        }

        $this->showForm = false;
    }

    public function delete(int $staffRoleId): void
    {
        $role = StaffRole::where('landlord_id', Auth::user()->landlord_id)->findOrFail($staffRoleId);

        if ($role->users()->exists()) {
            session()->flash('staff-role-error', 'Reassign staff before deleting this role.');
            return;
        }

        $role->delete();
        session()->flash('staff-role-saved', 'Staff role deleted.');
    }

    public function render()
    {
        $roles = StaffRole::where('landlord_id', Auth::user()->landlord_id)
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('livewire.admin-app.staff-roles', [
            'roles' => $roles,
            'catalog' => StaffPermissions::catalog(),
        ])->layout('components.layouts.app', ['title' => 'Staff Roles']);
    }
}
