<?php

namespace App\Livewire\AdminApp;

use App\Models\Tenant;
use App\Models\ViewingRequest;
use App\Notifications\DatabaseNotification;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * App-shell equivalent of App\Filament\Resources\ViewingRequestResource - the
 * app-shell is the default landing UI for every role now, so a landlord/
 * manager/caretaker who never opens the Filament "Advanced view" still needs a
 * real place to review and act on incoming viewing requests, not just a bell
 * notification. Admit/revoke reuse the exact same side effects as the
 * Filament resource (Tenant::booted() handles the rest downstream).
 */
class ViewingRequests extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $search = '';

    // Which request's inline action panel is open, and which action it's for.
    public ?int $activeActionId = null;

    public string $activeAction = '';

    public string $admitPhone = '';

    public string $revokeNotes = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function startAdmit(int $id): void
    {
        $request = $this->scopedRequest($id);

        $this->activeActionId = $id;
        $this->activeAction = 'admit';
        $this->admitPhone = $request->user->phone_number ?? '';
    }

    public function startRevoke(int $id): void
    {
        $this->activeActionId = $id;
        $this->activeAction = 'revoke';
        $this->revokeNotes = '';
    }

    public function cancelAction(): void
    {
        $this->reset(['activeActionId', 'activeAction', 'admitPhone', 'revokeNotes']);
    }

    public function confirmAdmit(): void
    {
        $this->validate([
            'admitPhone' => 'required|string|max:20',
        ]);

        $request = $this->scopedRequest($this->activeActionId);
        $house = $request->house;
        $user = $request->user;

        if (!$house || $house->house_status !== 'Vacant') {
            session()->flash('viewing-request-error', 'This house is no longer vacant. Revoke this request instead.');
            $this->cancelAction();

            return;
        }

        $user->update([
            'role' => 'tenant',
            'landlord_id' => $house->landlord_id,
            'phone_number' => $this->admitPhone,
        ]);

        // Everything downstream (house flips to Occupied, welcome SMS, admin
        // notifications, plan-limit enforcement) already happens via
        // Tenant::booted() - reused as-is, same as the Filament resource.
        Tenant::create([
            'user_id' => $user->id,
            'house_id' => $house->id,
            'tenant_name' => $user->name,
            'email' => $user->email,
            'phone_number' => $this->admitPhone,
            'date_admitted' => now(),
        ]);

        $request->update([
            'status' => 'admitted',
            'handled_by' => Auth::id(),
        ]);

        $this->cancelAction();
        session()->flash('viewing-request-admitted', "{$user->name} has been admitted as a tenant.");
    }

    public function confirmRevoke(): void
    {
        $request = $this->scopedRequest($this->activeActionId);

        $request->update([
            'status' => 'revoked',
            'admin_notes' => $this->revokeNotes ?: null,
            'handled_by' => Auth::id(),
        ]);

        $request->user?->notify(new DatabaseNotification(
            'Viewing Request Update',
            "Your viewing request for {$request->house?->house_name} was not successful." . ($this->revokeNotes ? " {$this->revokeNotes}" : ''),
            route('app.user.applications')
        ));

        $this->cancelAction();
        session()->flash('viewing-request-revoked', 'Viewing request revoked.');
    }

    protected function scopedRequest(int $id): ViewingRequest
    {
        // Re-scoped the same way as the list below - a crafted id must not let
        // a manager/caretaker act on a request outside their assigned properties.
        return StaffScope::onTenant(ViewingRequest::query())
            ->with(['user', 'house'])
            ->findOrFail($id);
    }

    public function render()
    {
        $query = StaffScope::onTenant(ViewingRequest::query())
            ->with(['user', 'house.location'])
            ->latest('requested_at');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('phone_number', 'like', $term))
                    ->orWhereHas('house', fn ($h) => $h->where('house_name', 'like', $term));
            });
        }

        $pendingCount = (clone StaffScope::onTenant(ViewingRequest::query()))->where('status', 'pending')->count();

        return view('livewire.admin-app.viewing-requests', [
            'requests' => $query->paginate(10),
            'pendingCount' => $pendingCount,
        ])->layout('components.layouts.app', ['title' => 'Viewing Requests']);
    }
}
