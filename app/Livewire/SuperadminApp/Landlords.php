<?php

namespace App\Livewire\SuperadminApp;

use App\Models\Landlord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Landlords extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public ?int $editingId = null;

    public ?int $decidingVerificationId = null;
    public string $rejectionNotes = '';

    public string $name = '';
    public string $contact_email = '';
    public string $phone_number = '';
    public string $status = 'active';
    public bool $c2b_enabled = false;

    public string $owner_name = '';
    public string $owner_email = '';
    public string $owner_password = '';

    protected function rules(): array
    {
        $landlord = $this->editingId ? Landlord::find($this->editingId) : null;

        return [
            'name' => 'required|string|max:255',
            'contact_email' => ['required', 'email', 'max:255', Rule::unique('landlords', 'contact_email')->ignore($this->editingId)],
            'phone_number' => 'nullable|string|max:255',
            'status' => 'required|in:active,suspended',
            'c2b_enabled' => 'nullable|boolean',
            'owner_name' => 'nullable|string|max:255',
            'owner_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($landlord?->owner?->id)],
            // Blank keeps the owner's current password, matching the same
            // pattern used across the AdminApp Users edit form.
            'owner_password' => 'nullable|string|min:6',
        ];
    }

    public function startEdit(int $landlordId): void
    {
        $landlord = Landlord::findOrFail($landlordId);
        $owner = $landlord->owner;

        $this->editingId = $landlord->id;
        $this->name = $landlord->name;
        $this->contact_email = $landlord->contact_email;
        $this->phone_number = $landlord->phone_number ?? '';
        $this->status = $landlord->status;
        $this->c2b_enabled = (bool) $landlord->c2b_enabled;

        $this->owner_name = $owner?->name ?? '';
        $this->owner_email = $owner?->email ?? '';
        $this->owner_password = '';

        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->reset([
            'showForm', 'editingId', 'name', 'contact_email', 'phone_number',
            'status', 'c2b_enabled', 'owner_name', 'owner_email', 'owner_password',
        ]);
        $this->status = 'active';
    }

    public function save(): void
    {
        $this->validate();

        $landlord = Landlord::findOrFail($this->editingId);

        $landlord->update([
            'name' => $this->name,
            'contact_email' => $this->contact_email,
            'phone_number' => $this->phone_number ?: null,
            'status' => $this->status,
            'c2b_enabled' => $this->c2b_enabled,
        ]);

        $owner = $landlord->owner;

        if ($owner) {
            $ownerData = array_filter([
                'name' => $this->owner_name ?: null,
                'email' => $this->owner_email ?: null,
            ]);

            if ($this->owner_password !== '') {
                $ownerData['password'] = Hash::make($this->owner_password);
            }

            if (! empty($ownerData)) {
                $owner->update($ownerData);
            }
        } elseif ($this->owner_name || $this->owner_email || $this->owner_password) {
            // No linked role='landlord' User to update - surface this loudly
            // rather than silently discarding what was just typed and saved.
            $this->cancelForm();
            session()->flash('landlord-error', 'Business details were saved, but this business has no linked login account (role=landlord User) to apply the name/email/password change to.');
            return;
        }

        $this->cancelForm();
        session()->flash('landlord-saved', 'Landlord updated.');
    }

    public function approveVerification(int $landlordId): void
    {
        Landlord::findOrFail($landlordId)->approveVerification(Auth::id());
        session()->flash('landlord-saved', 'Landlord verified.');
    }

    public function startReject(int $landlordId): void
    {
        $this->decidingVerificationId = $landlordId;
        $this->rejectionNotes = '';
    }

    public function cancelReject(): void
    {
        $this->decidingVerificationId = null;
        $this->rejectionNotes = '';
    }

    public function confirmReject(): void
    {
        Landlord::findOrFail($this->decidingVerificationId)->rejectVerification(Auth::id(), $this->rejectionNotes ?: null);
        $this->cancelReject();
        session()->flash('landlord-saved', 'Verification rejected.');
    }

    public function render()
    {
        $landlords = Landlord::with('currentSubscription.package')->latest()->paginate(10);

        return view('livewire.superadmin-app.landlords', ['landlords' => $landlords])
            ->layout('components.layouts.app', ['title' => 'Landlords']);
    }
}
