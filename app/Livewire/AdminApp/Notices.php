<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\NoticeToVacate;
use App\Support\StaffPermissions;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Notices extends Component
{
    use WithPagination;
    use ExportsCsv;

    public string $search = '';
    public string $statusFilter = '';

    public ?int $decidingNoticeId = null;
    public string $decidingAction = '';
    public string $adminNotes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function startDeciding(int $id, string $action): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::DECIDE_NOTICES), 403);

        $this->decidingNoticeId = $id;
        $this->decidingAction = $action;
        $this->adminNotes = '';
    }

    public function cancelDeciding(): void
    {
        $this->decidingNoticeId = null;
        $this->decidingAction = '';
        $this->adminNotes = '';
    }

    public function confirmDecision(): void
    {
        abort_unless(Auth::user()->hasPermission(StaffPermissions::DECIDE_NOTICES), 403);

        $notice = StaffScope::onTenantChild(NoticeToVacate::query())->findOrFail($this->decidingNoticeId);

        if ($this->decidingAction === 'approve') {
            $notice->approve($this->adminNotes ?: null);
        } elseif ($this->decidingAction === 'deny') {
            $notice->deny($this->adminNotes ?: null);
        }

        $this->cancelDeciding();
        session()->flash('notice-decided', 'Notice updated.');
    }

    protected function filteredQuery()
    {
        $query = StaffScope::onTenantChild(NoticeToVacate::query())->with('tenant')->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->whereHas('tenant', fn ($t) => $t->where('tenant_name', 'like', $term)->orWhere('phone_number', 'like', $term));
        }

        return $query;
    }

    public function export()
    {
        $notices = $this->filteredQuery()->get();

        return $this->streamCsv(
            'notices-to-vacate.csv',
            ['Tenant', 'Phone', 'House', 'Vacate Date', 'Reason', 'Status', 'Approved On'],
            $notices->map(fn (NoticeToVacate $notice) => [
                $notice->tenant?->tenant_name,
                $notice->tenant?->phone_number,
                $notice->tenant?->house?->house_name,
                optional($notice->vacate_date)->format('Y-m-d'),
                $notice->reason_type,
                $notice->status,
                optional($notice->approved_at)->format('Y-m-d H:i'),
            ])
        );
    }

    public function render()
    {
        $notices = $this->filteredQuery()->paginate(15);

        return view('livewire.admin-app.notices', [
            'notices' => $notices,
            'canDecideNotices' => Auth::user()->hasPermission(StaffPermissions::DECIDE_NOTICES),
        ])->layout('components.layouts.app', ['title' => 'Notices']);
    }
}
