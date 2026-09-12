<?php

namespace App\Livewire\SuperadminApp;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Platform-wide account directory - every User row regardless of role or
 * landlord, for oversight/support. This is deliberately a blunt tool (no
 * per-landlord scoping to check, no plan limits to enforce) - a landlord's own
 * Staff page (App\Livewire\AdminApp\Users) is still where day-to-day staff
 * management happens, and a landlord's own business record stays on
 * SuperadminApp\Landlords. This is for the rarer "fix/inspect any account"
 * case that only a superadmin should reach.
 */
class Users extends Component
{
    use WithPagination;

    // Every literal role value the app assigns - not landlord-scoped custom
    // roles (StaffRole), which stay under that landlord's own Staff page since
    // this screen has no concept of "which landlord's custom roles apply".
    public const ROLES = ['user', 'tenant', 'landlord', 'admin', 'manager', 'caretaker', 'agent', 'staff', 'superadmin'];

    public string $search = '';
    public string $roleFilter = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone_number = '';
    public string $password = '';
    public string $role = 'user';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone_number' => 'nullable|string|max:255',
            // Blank keeps the current password, same convention as the
            // landlord-scoped Staff page.
            'password' => $this->editingId ? 'nullable|string|min:6' : 'required|string|min:6',
            'role' => 'required|in:' . implode(',', self::ROLES),
        ];
    }

    public function startEdit(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone_number = $user->phone_number ?? '';
        $this->password = '';
        $this->role = $user->role;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'email', 'phone_number', 'password', 'role']);
        $this->role = 'user';
    }

    public function save(): void
    {
        $this->validate();

        $user = User::findOrFail($this->editingId);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number ?: null,
            'role' => $this->role,
        ];

        // A role change away from a custom staff role leaves staff_role_id
        // dangling otherwise - clear it so the badge/permissions stop
        // referencing a role that no longer applies.
        if ($this->role !== 'staff') {
            $data['staff_role_id'] = null;
        }

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        $user->update($data);

        $this->cancelForm();
        session()->flash('user-saved', 'Account updated.');
    }

    public function delete(int $userId): void
    {
        abort_if($userId === Auth::id(), 403, "You can't delete your own account.");

        User::findOrFail($userId)->delete();

        session()->flash('user-saved', 'Account deleted.');
    }

    public function render()
    {
        $query = User::with(['landlord', 'staffRole'])->latest();

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(fn ($q) => $q
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('phone_number', 'like', $term));
        }

        if ($this->roleFilter !== '') {
            $query->where('role', $this->roleFilter);
        }

        $users = $query->paginate(20);

        $roleCounts = User::selectRaw('role, count(*) as count')->groupBy('role')->pluck('count', 'role');

        return view('livewire.superadmin-app.users', [
            'users' => $users,
            'roleCounts' => $roleCounts,
        ])->layout('components.layouts.app', ['title' => 'Users']);
    }
}
