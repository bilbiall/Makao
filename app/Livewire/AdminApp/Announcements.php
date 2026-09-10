<?php

namespace App\Livewire\AdminApp;

use App\Models\Announcement;
use App\Models\Location;
use App\Support\StaffPermissions;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Announcements extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public string $subject = '';
    public string $message = '';
    public string $location_id = '';
    public bool $send_sms = false;
    public bool $send_email = false;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::SEND_ANNOUNCEMENTS), 403);
    }

    protected function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'location_id' => 'nullable|exists:locations,id',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
        ];
    }

    public function startCreate(): void
    {
        $this->reset(['subject', 'message', 'location_id', 'send_sms', 'send_email']);
        $this->showForm = true;
    }

    public function send(): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::SEND_ANNOUNCEMENTS), 403);

        $this->validate();

        if (!$this->send_sms && !$this->send_email) {
            $this->addError('send_sms', 'Pick at least one channel - SMS, Email, or both.');
            return;
        }

        $announcement = Announcement::create([
            'landlord_id' => Auth::user()->landlord_id,
            'sent_by' => Auth::id(),
            'location_id' => $this->location_id ?: null,
            'subject' => $this->subject,
            'message' => $this->message,
            'send_sms' => $this->send_sms,
            'send_email' => $this->send_email,
        ]);

        $announcement->send();

        $this->reset(['subject', 'message', 'location_id', 'send_sms', 'send_email', 'showForm']);
        session()->flash(
            'announcement-sent',
            "Sent to {$announcement->recipients_total} tenant" . ($announcement->recipients_total === 1 ? '' : 's') . '.'
        );
    }

    public function render()
    {
        $query = Announcement::where('landlord_id', Auth::user()->landlord_id)->with(['location', 'sentBy']);

        if (StaffScope::isScopedStaff()) {
            $locationIds = StaffScope::locationIds();
            $query->where(fn ($q) => $q->whereNull('location_id')->orWhereIn('location_id', $locationIds));
        } elseif (StaffScope::isAgent()) {
            $query->whereRaw('1 = 0');
        }

        $announcements = $query->latest('sent_at')->paginate(10);

        $locationsQuery = Location::where('landlord_id', Auth::user()->landlord_id)->orderBy('location_name');
        if (StaffScope::isScopedStaff()) {
            $locationsQuery->whereIn('id', StaffScope::locationIds());
        } elseif (StaffScope::isAgent()) {
            $locationsQuery->whereRaw('1 = 0');
        }
        $locations = $locationsQuery->get();

        return view('livewire.admin-app.announcements', [
            'announcements' => $announcements,
            'locations' => $locations,
        ])->layout('components.layouts.app', ['title' => 'Announcements']);
    }
}
