<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\House;
use App\Models\Landlord;
use App\Models\Location;
use App\Models\StaffAssignment;
use App\Models\User;
use App\Services\PackageLimitService;
use Filament\Notifications\Notification;
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
    public string $role = 'caretaker';
    public array $location_ids = [];
    public array $house_ids = [];

    public bool $showNotifyForm = false;
    public array $notify_tenant_ids = [];
    public string $notify_message = '';

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

        return [
            'name' => 'required|string|max:255',
            // Editing keeps its own email, so the uniqueness check must ignore
            // the record being edited (no-op for create, where editingId is null).
            'email' => 'required|email|unique:users,email,' . ($this->editingId ?? 'NULL'),
            // Blank on edit = keep the existing password, matching Filament's
            // "dehydrated only if filled" behaviour.
            'password' => $this->editingId ? 'nullable|string|min:6' : 'required|string|min:6',
            'role' => 'required|in:' . $allowedRoles,
            'location_ids' => 'required_if:role,manager,caretaker|array',
            'location_ids.*' => 'exists:locations,id',
            'house_ids' => 'required_if:role,agent|array',
            'house_ids.*' => 'exists:houses,id',
        ];
    }

    public function create(): void
    {
        $this->validate();

        abort_if($this->role === 'admin' && Auth::user()->role !== 'landlord', 403);

        $landlord = Landlord::find(Auth::user()->landlord_id);
        $limitService = app(PackageLimitService::class);

        if (in_array($this->role, ['admin', 'manager', 'caretaker', 'agent']) && !$limitService->canAdd('users', $landlord)) {
            session()->flash('user-error', $limitService->limitMessage('users', $landlord));
            return;
        }

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'landlord_id' => Auth::user()->landlord_id,
        ]);

        $this->syncAssignments($user);

        $this->cancelForm();
        session()->flash('user-created', 'Staff account created successfully.');
    }

    public function startEdit(int $userId): void
    {
        $user = User::where('landlord_id', Auth::user()->landlord_id)
            ->whereIn('role', ['admin', 'manager', 'caretaker', 'agent'])
            ->findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role;
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

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
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
            ->whereIn('role', ['admin', 'manager', 'caretaker', 'agent'])
            ->findOrFail($userId);

        abort_if($user->id === Auth::id(), 403);

        $user->staffAssignments()->delete();
        $user->delete();

        session()->flash('user-created', 'Staff account removed.');
    }

    protected function syncAssignments(User $user): void
    {
        if (in_array($this->role, ['manager', 'caretaker'])) {
            foreach ($this->location_ids as $locationId) {
                StaffAssignment::create([
                    'user_id' => $user->id,
                    'location_id' => $locationId,
                    'role' => $this->role,
                    'assigned_by' => Auth::id(),
                ]);
            }
        }

        if ($this->role === 'agent') {
            foreach ($this->house_ids as $houseId) {
                StaffAssignment::create([
                    'user_id' => $user->id,
                    'house_id' => $houseId,
                    'role' => 'agent',
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

    public function startNotify(): void
    {
        $this->reset(['notify_tenant_ids', 'notify_message']);
        $this->showNotifyForm = true;
    }

    public function sendNotification(): void
    {
        $this->validate([
            'notify_tenant_ids' => 'required|array|min:1',
            'notify_message' => 'required|string',
        ]);

        $tenants = User::where('role', 'tenant')
            ->where('landlord_id', Auth::user()->landlord_id)
            ->whereIn('id', $this->notify_tenant_ids)
            ->get();

        foreach ($tenants as $tenant) {
            Notification::make()
                ->title('Message from Admin')
                ->body($this->notify_message)
                ->sendToDatabase($tenant);
        }

        $this->showNotifyForm = false;
        $this->reset(['notify_tenant_ids', 'notify_message']);
        session()->flash('user-created', "Notification sent to {$tenants->count()} tenant(s).");
    }

    public function export()
    {
        $landlordId = Auth::user()->landlord_id;

        $staff = User::whereIn('role', ['admin', 'manager', 'caretaker', 'agent'])
            ->where('landlord_id', $landlordId)
            ->with('assignedLocations')
            ->orderBy('name')
            ->get();

        return $this->streamCsv(
            'staff.csv',
            ['Name', 'Email', 'Role', 'Assigned Locations'],
            $staff->map(fn (User $user) => [
                $user->name,
                $user->email,
                $user->role,
                $user->assignedLocations->pluck('location_name')->join('; '),
            ])
        );
    }

    public function render()
    {
        $landlordId = Auth::user()->landlord_id;

        $staff = User::whereIn('role', ['admin', 'manager', 'caretaker', 'agent'])
            ->where('landlord_id', $landlordId)
            ->with('assignedLocations')
            ->orderBy('name')
            ->paginate(15);

        $locations = Location::orderBy('location_name')->get();
        $shortTermHouses = House::where('landlord_id', $landlordId)->where('listing_mode', 'short_term')->get();
        $tenants = User::where('role', 'tenant')->where('landlord_id', $landlordId)->orderBy('name')->get();

        return view('livewire.admin-app.users', [
            'staff' => $staff,
            'locations' => $locations,
            'shortTermHouses' => $shortTermHouses,
            'tenants' => $tenants,
            'canAssignAdmin' => Auth::user()->role === 'landlord',
        ])
            ->layout('components.layouts.app', ['title' => 'Staff']);
    }
}
