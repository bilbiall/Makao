<?php

namespace App\Livewire\SuperadminApp;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Platform-wide, read-only account directory - every User row regardless of
 * role or landlord, for oversight/support. Not a management screen: editing a
 * landlord's own staff stays on that landlord's own Staff page
 * (App\Livewire\AdminApp\Users), and a landlord's own business record stays on
 * SuperadminApp\Landlords - this is just "who exists on the platform."
 */
class Users extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
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
