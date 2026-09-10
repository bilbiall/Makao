<?php

namespace App\Livewire\AdminApp;

use App\Livewire\Concerns\ExportsCsv;
use App\Models\Issue;
use App\Models\Location;
use App\Support\StaffScope;
use Livewire\Component;
use Livewire\WithPagination;

class Issues extends Component
{
    use WithPagination;
    use ExportsCsv;

    public string $statusFilter = '';

    public string $locationFilter = '';

    public array $selected = [];

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus($issueId, $status): void
    {
        $issue = StaffScope::onTenantChild(Issue::query())->findOrFail($issueId);
        $issue->status = $status;
        $issue->save();
    }

    public function delete(int $issueId): void
    {
        StaffScope::onTenantChild(Issue::query())->findOrFail($issueId)->delete();
        $this->selected = array_diff($this->selected, [$issueId]);
        session()->flash('issue-deleted', 'Issue deleted.');
    }

    public function deleteSelected(): void
    {
        if (empty($this->selected)) {
            return;
        }

        StaffScope::onTenantChild(Issue::query())->whereIn('id', $this->selected)->delete();
        $count = count($this->selected);
        $this->selected = [];
        session()->flash('issue-deleted', "{$count} issue(s) deleted.");
    }

    protected function filteredQuery()
    {
        $query = StaffScope::onTenantChild(Issue::query())->with('tenant.house.location')->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->locationFilter) {
            $query->whereRelation('tenant.house', 'location_id', $this->locationFilter);
        }

        return $query;
    }

    public function locationsForFilter()
    {
        return Location::query()
            ->whereHas('houses.tenant.issues')
            ->orderBy('location_name')
            ->pluck('location_name', 'id');
    }

    public function export()
    {
        $issues = $this->filteredQuery()->get();

        return $this->streamCsv(
            'issues.csv',
            ['Tenant', 'House', 'Title', 'Description', 'Status', 'Reported On'],
            $issues->map(fn (Issue $issue) => [
                $issue->tenant?->tenant_name,
                $issue->tenant?->house?->house_name,
                $issue->title,
                $issue->description,
                $issue->status,
                optional($issue->created_at)->format('Y-m-d H:i'),
            ])
        );
    }

    public function render()
    {
        $issues = $this->filteredQuery()->paginate(10);

        return view('livewire.admin-app.issues', [
            'issues' => $issues,
            'locationOptions' => $this->locationsForFilter(),
        ])->layout('components.layouts.app', ['title' => 'Issues']);
    }
}
