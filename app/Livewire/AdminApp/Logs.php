<?php

namespace App\Livewire\AdminApp;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * App-shell counterpart to the Filament Logs page (Superadmin/Analytics side
 * has its own version) - same ActivityLog data, filtered to this landlord
 * only (see ActivityLog's BelongsToLandlord scope), with filters by user,
 * activity type, and date.
 */
class Logs extends Component
{
    use WithPagination;

    public ?int $log_user = null;

    public ?string $log_action = null;

    public ?string $log_from = null;

    public ?string $log_to = null;

    public function updating(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['log_user', 'log_action', 'log_from', 'log_to']);
    }

    public function render()
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);

        $query = ActivityLog::with('user')->latest();

        if ($this->log_user) {
            $query->where('user_id', $this->log_user);
        }

        if ($this->log_action) {
            $query->where('action', $this->log_action);
        }

        if ($this->log_from) {
            $query->whereDate('created_at', '>=', $this->log_from);
        }

        if ($this->log_to) {
            $query->whereDate('created_at', '<=', $this->log_to);
        }

        $logs = $query->paginate(20);

        // Both option lists are scoped the same way ActivityLog itself is (this
        // landlord's own logs only - see ActivityLog's BelongsToLandlord global
        // scope), so a filter never offers a choice that could leak another
        // landlord's user or action into view.
        $usersList = ActivityLog::query()
            ->whereNotNull('user_id')
            ->with('user:id,name')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $actionsList = ActivityLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->mapWithKeys(fn ($action) => [$action => ucfirst(str_replace('_', ' ', $action))]);

        return view('livewire.admin-app.logs', [
            'logs' => $logs,
            'usersList' => $usersList,
            'actionsList' => $actionsList,
        ])->layout('components.layouts.app', ['title' => 'Logs']);
    }
}
