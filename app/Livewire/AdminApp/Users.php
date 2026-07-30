<?php

namespace App\Livewire\AdminApp;

use App\Models\Location;
use App\Models\User;
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
    public $location_id = '';

    public function mount(): void
    {
        // Matches UserResource::canAccess() - caretakers don't manage staff.
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            // Landlord/superadmin are deliberately excluded here - matching UserResource's
            // form, staff can only create admin/caretaker/tenant accounts, never accounts
            // that could self-escalate their own access.
            'role' => 'required|in:admin,caretaker,tenant',
            'location_id' => 'required_if:role,caretaker|nullable|exists:locations,id',
        ];
    }

    public function create(): void
    {
        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => $this->role,
            'location_id' => $this->role === 'caretaker' ? $this->location_id : null,
            'landlord_id' => Auth::user()->landlord_id,
        ]);

        $this->reset(['name', 'email', 'password', 'location_id', 'showForm']);
        $this->role = 'caretaker';
        session()->flash('user-created', 'Staff account created successfully.');
    }

    public function render()
    {
        $landlordId = Auth::user()->landlord_id;

        $staff = User::whereIn('role', ['admin', 'caretaker'])
            ->where('landlord_id', $landlordId)
            ->orderBy('name')
            ->get();

        $locations = Location::orderBy('location_name')->get();

        return view('livewire.admin-app.users', ['staff' => $staff, 'locations' => $locations])
            ->layout('components.layouts.app', ['title' => 'Staff']);
    }
}
