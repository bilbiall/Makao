<?php

namespace App\Livewire\AdminApp;

use App\Models\Issue;
use App\Support\CaretakerScope;
use Livewire\Component;
use Livewire\WithPagination;

class Issues extends Component
{
    use WithPagination;

    public function updateStatus($issueId, $status): void
    {
        $issue = CaretakerScope::onTenantChild(Issue::query())->findOrFail($issueId);
        $issue->status = $status;
        $issue->save();
    }

    public function render()
    {
        $issues = CaretakerScope::onTenantChild(Issue::query())
            ->with('tenant')
            ->latest()
            ->paginate(10);

        return view('livewire.admin-app.issues', ['issues' => $issues])
            ->layout('components.layouts.app', ['title' => 'Issues']);
    }
}
