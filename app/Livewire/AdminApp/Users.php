<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\House;
use App\Models\Landlord;
use App\Models\Location;
use App\Models\StaffAssignment;
use App\Models\User;
use App\Services\PackageLimitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;
    use ExportsCsv;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    // Either a legacy literal role ('manager'/'caretaker'/'agent'/'admin'/'tenant')
    // or "custom:{staff_role_id}" for a landlord-defined role (see StaffRole) -
    // resolved into the real role/staff_role_id columns in create()/update().
    public string $role = 'caretaker';
    public array $location_ids = [];
    public array $house_ids = [];

    public function mount(): void
    {
        // Matches UserResource::canAccess() - caretakers/managers/agents don't manage staff.
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);
    }

    protected function rules(): array
    {
        // Landlord/superadmin are deliberately excluded here - matching UserResource's
        // form, staff can only create manager/caretaker/agent/tenant accounts, never
        // accounts that could self-escalate their own access. 'admin' is only allowed
        // when the property owner themselves is filling this form - a staff 'admin'
        // account must not be able to mint peer admins (see create() for the same check
        // enforced server-side, since the client-side rule alone doesn't stop a
        // tampered request).
        $allowedRoles = Auth::user()->role === 'landlord'
            ? 'admin,manager,caretaker,agent,tenant'
            : 'manager,caretaker,agent,tenant';

        // A landlord-defined custom role is submitted as "custom:{id}" (see the
        // role <select> in the blade) - validate it against this landlord's own
        // StaffRole records rather than allowing an arbitrary id.
        $customRoleIds = \App\Models\StaffRole::where('landlord_id', Auth::user()->landlord_id)->pluck('id');
        $customRolePattern = $customRoleIds->isEmpty()
            ? 'custom:none'
            : $customRoleIds->map(fn ($id) => "custom:{$id}")->implode(',');

        return [
            'name' => 'required|string|max:255',
            // Editing keeps its own email, so the uniqueness check must ignore
            // the record being edited (no-op for create, where editingId is null).
            'email' => 'required|email|unique:users,email,' . ($this->editingId ?? 'NULL'),
            // Blank on edit = keep the existing password, matching Filament's
            // "dehydrated only if filled" behaviour.
            'password' => $this->editingId ? 'nullable|string|min:6' : 'required|string|min:6',
            'role' => 'required|in:' . $allowedRoles . ',' . $customRolePattern,
            'location_ids' => 'required_if:role,manager,caretaker|array',
            'location_ids.*' => 'exists:locations,id',
            'house_ids' => 'required_if:role,agent|array',
            'house_ids.*' => 'exists:houses,id',
        ];
    }

    /**
     * Resolves the submitted role select value into the real columns to persist:
     * [role, staff_role_id, scope_type] - scope_type is only meaningful for a
     * custom role, and drives whether location_ids or house_ids gets synced.
     */
    protected function resolveRoleSelection(): array
    {
        if (str_starts_with($this->role, 'custom:')) {
            $staffRole = \App\Models\StaffRole::find((int) substr($this->role, strlen('custom:')));

            return ['staff', $staffRole?->id, $staffRole?->scope_type];
        }

        return [$this->role, null, null];
    }

    public function create(): void
    {
        $this->validate();

        abort_if($this->role === 'admin' && Auth::user()->role !== 'landlord', 403);

        $landlord = Landlord::find(Auth::user()->landlord_id);
        $limitService = app(PackageLimitService::class);

        if ((in_array($this->role, ['admin', 'manager', 'caretaker', 'agent']) || str_starts_with($this->role, 'custom:')) && !$limitService->canAdd('users', $landlord)) {
            session()->flash('user-error', $limitService->limitMessage('users', $landlord));
            return;
        }

        [$role, $staffRoleId] = $this->resolveRoleSelection();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $role,
            'staff_role_id' => $staffRoleId,
            'landlord_id' => Auth::user()->landlord_id,
        ]);

        $this->syncAssignments($user);

        $this->cancelForm();
        session()->flash('user-created', 'Staff account created successfully.');
    }

    public function startEdit(int $userId): void
    {
        $user = User::where('landlord_id', Auth::user()->landlord_id)
            ->whereIn('role', ['admin', 'manager', 'caretaker', 'agent', 'staff'])
            ->findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        // A custom-role assignee's select value must round-trip back to
        // "custom:{id}", not the literal 'staff'.
        $this->role = $user->staff_role_id ? "custom:{$user->staff_role_id}" : $user->role;
        $this->location_ids = $user->staffAssignments()->whereNotNull('location_id')->pluck('location_id')->all();
        $this->house_ids = $user->staffAssignments()->whereNotNull('house_id')->pluck('house_id')->all();
        $this->showForm = true;
    }

    public function update(): void
    {
        $this->validate();

        $user = User::where('landlord_id', Auth::user()->landlord_id)->findOrFail($this->editingId);

        // Only the property owner may grant Admin - promoting an existing staff
        // account is blocked the same way creating one is, unless it was already
        // admin (editing other fields on an existing admin account).
        abort_if($this->role === 'admin' && $user->role !== 'admin' && Auth::user()->role !== 'landlord', 403);

        [$role, $staffRoleId] = $this->resolveRoleSelection();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $role,
            'staff_role_id' => $staffRoleId,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        $user->update($data);

        // Replace this user's assignments wholesale with whatever the form submitted.
        $user->staffAssignments()->delete();
        $this->syncAssignments($user);

        $this->cancelForm();
        session()->flash('user-created', 'Staff account updated.');
    }

    public function delete(int $userId): void
    {
        $user = User::where('landlord_id', Auth::user()->landlord_id)
            ->whereIn('role', ['admin', 'manager', 'caretaker', 'agent', 'staff'])
            ->findOrFail($userId);

        abort_if($user->id === Auth::id(), 403);

        $user->staffAssignments()->delete();
        $user->delete();

        session()->flash('user-created', 'Staff account removed.');
    }

    protected function syncAssignments(User $user): void
    {
        [, , $scopeType] = $this->resolveRoleSelection();

        if (in_array($this->role, ['manager', 'caretaker']) || $scopeType === 'location') {
            foreach ($this->location_ids as $locationId) {
                StaffAssignment::create([
                    'user_id' => $user->id,
                    'location_id' => $locationId,
                    'role' => $user->role,
                    'assigned_by' => Auth::id(),
                ]);
            }
        }

        if ($this->role === 'agent' || $scopeType === 'house') {
            foreach ($this->house_ids as $houseId) {
                StaffAssignment::create([
                    'user_id' => $user->id,
                    'house_id' => $houseId,
                    'role' => $user->role,
                    'assigned_by' => Auth::id(),
                ]);
            }
        }
    }

    public function cancelForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'location_ids', 'house_ids', 'showForm']);
        $this->role = 'caretaker';
    }

    public function export()
    {
        $landlordId = Auth::user()->landlord_id;

        $staff = User::whereIn('role', ['admin', 'manager', 'caretaker', 'agent', 'staff'])
            ->where('landlord_id', $landlordId)
            ->with(['assignedLocations', 'staffRole'])
            ->orderBy('name')
            ->get();

        return $this->streamCsv(
            'staff.csv',
            ['Name', 'Email', 'Role', 'Assigned Locations'],
            $staff->map(fn (User $user) => [
                $user->name,
                $user->email,
                $user->staffRole?->name ?? $user->role,
                $user->assignedLocations->pluck('location_name')->join('; '),
            ])
        );
    }

    public function render()
    {
        $landlordId = Auth::user()->landlord_id;

        $staff = User::whereIn('role', ['admin', 'manager', 'caretaker', 'agent', 'staff'])
            ->where('landlord_id', $landlordId)
            ->with(['assignedLocations', 'staffRole'])
            ->orderBy('name')
            ->paginate(15);

        $customRoles = \App\Models\StaffRole::where('landlord_id', $landlordId)->orderBy('name')->get();

        $locations = Location::orderBy('location_name')->get();
        $shortTermHouses = House::where('landlord_id', $landlordId)->where('listing_mode', 'short_term')->get();

        return view('livewire.admin-app.users', [
            'staff' => $staff,
            'locations' => $locations,
            'shortTermHouses' => $shortTermHouses,
            'customRoles' => $customRoles,
            'canAssignAdmin' => Auth::user()->role === 'landlord',
        ])
            ->layout('components.layouts.app', ['title' => 'Staff']);
    }
}
