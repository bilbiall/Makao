<?php

namespace App\Livewire\AdminApp;

use App\Models\LandlordRoleDefault;
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

    // Built-in role (manager/caretaker/agent) being customised for this landlord only.
    public ?string $editingBuiltIn = null;
    public array $builtInPermissions = [];

    public function mount(): void
    {
        // Manager (the landlord's day-to-day deputy, scoped to their assigned
        // properties) can also define/edit custom staff roles - unlike Caretaker
        // and Agent, who are staffed by these roles rather than authoring them.
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord', 'manager']), 403);
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

    protected function canEditBuiltIn(): bool
    {
        // The landlord's own call - a manager shouldn't be able to widen their own access.
        return in_array(Auth::user()->role, ['admin', 'landlord'], true) && Auth::user()->landlord_id;
    }

    public function startEditBuiltIn(string $role): void
    {
        abort_unless($this->canEditBuiltIn() && in_array($role, StaffPermissions::BUILT_IN_ROLES, true), 403);

        $this->showForm = false;
        $this->editingBuiltIn = $role;
        $this->builtInPermissions = StaffPermissions::builtInFor(Auth::user()->landlord_id, $role);
    }

    public function saveBuiltIn(): void
    {
        abort_unless($this->canEditBuiltIn() && in_array($this->editingBuiltIn, StaffPermissions::BUILT_IN_ROLES, true), 403);
        $this->validate(['builtInPermissions' => 'array', 'builtInPermissions.*' => 'in:' . implode(',', StaffPermissions::all())]);

        LandlordRoleDefault::updateOrCreate(
            ['landlord_id' => Auth::user()->landlord_id, 'role' => $this->editingBuiltIn],
            ['permissions' => array_values($this->builtInPermissions)]
        );
        StaffPermissions::flushOverrideCache();

        $this->editingBuiltIn = null;
        session()->flash('staff-role-saved', 'Built-in role updated for your site.');
    }

    public function resetBuiltIn(string $role): void
    {
        abort_unless($this->canEditBuiltIn() && in_array($role, StaffPermissions::BUILT_IN_ROLES, true), 403);

        LandlordRoleDefault::where('landlord_id', Auth::user()->landlord_id)->where('role', $role)->delete();
        StaffPermissions::flushOverrideCache();

        $this->editingBuiltIn = null;
        session()->flash('staff-role-saved', 'Built-in role restored to the platform default.');
    }

    public function startCreate(): void
    {
        $this->editingBuiltIn = null;
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
            'builtInRoles' => $this->builtInRoles(),
            'canEditBuiltIn' => $this->canEditBuiltIn(),
            'customisedRoles' => LandlordRoleDefault::where('landlord_id', Auth::user()->landlord_id)->pluck('role')->all(),
        ])->layout('components.layouts.app', ['title' => 'Staff Roles']);
    }

    /**
     * The three legacy roles every staff account still falls back to until a
     * custom staff role is created and assigned - shown alongside the
     * custom roles list so a landlord can see what scope/permissions "Manager",
     * "Caretaker" and "Agent" already carry before deciding whether they need
     * something different. Mirrors StaffScope (property vs unit scope) and
     * StaffPermissions::defaultFor() (which slugs each one carries) exactly -
     * this is presentation only, not a second source of truth for the rules.
     */
    protected function builtInRoles(): array
    {
        $landlordId = Auth::user()->landlord_id;

        return collect([
            ['role' => 'manager', 'name' => 'Manager', 'scope_type' => 'location', 'summary' => 'Full access'],
            ['role' => 'caretaker', 'name' => 'Caretaker', 'scope_type' => 'location', 'summary' => 'On-site tasks only'],
            ['role' => 'agent', 'name' => 'Agent', 'scope_type' => 'house', 'summary' => 'Bookings only'],
        ])->map(function (array $r) use ($landlordId) {
            $r['permissions'] = StaffPermissions::builtInFor($landlordId, $r['role']);
            return $r;
        })->all();
    }
}
