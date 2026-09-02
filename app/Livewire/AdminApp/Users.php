<?php

namespace App\Livewire\AdminApp;

use App\Models\House;
use App\Models\Landlord;
use App\Models\Location;
use App\Models\StaffAssignment;
use App\Models\User;
use App\Services\PackageLimitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Users extends Component
{
    public bool $showForm = false;
    public string $name = '';
    public string $email = '';
    public string $password = '';
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

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
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

        $this->reset(['name', 'email', 'password', 'location_ids', 'house_ids', 'showForm']);
        $this->role = 'caretaker';
        session()->flash('user-created', 'Staff account created successfully.');
    }

    public function render()
    {
        $landlordId = Auth::user()->landlord_id;

        $staff = User::whereIn('role', ['admin', 'manager', 'caretaker', 'agent'])
            ->where('landlord_id', $landlordId)
            ->with('assignedLocations')
            ->orderBy('name')
            ->get();

        $locations = Location::orderBy('location_name')->get();
        $shortTermHouses = House::where('landlord_id', $landlordId)->where('listing_mode', 'short_term')->get();

        return view('livewire.admin-app.users', [
            'staff' => $staff,
            'locations' => $locations,
            'shortTermHouses' => $shortTermHouses,
            'canAssignAdmin' => Auth::user()->role === 'landlord',
        ])
            ->layout('components.layouts.app', ['title' => 'Staff']);
    }
}
