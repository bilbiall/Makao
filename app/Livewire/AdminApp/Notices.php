<?php

namespace App\Livewire\AdminApp;

use App\Models\NoticeToVacate;
use App\Support\CaretakerScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notices extends Component
{
    public function approve($id): void
    {
        $notice = CaretakerScope::onTenantChild(NoticeToVacate::query())->findOrFail($id);
        $notice->status = 'approved';
        $notice->approved_at = now();
        $notice->approved_by = Auth::id();
        $notice->save();
    }

    public function deny($id): void
    {
        $notice = CaretakerScope::onTenantChild(NoticeToVacate::query())->findOrFail($id);
        $notice->status = 'denied';
        $notice->denied_at = now();
        $notice->approved_by = Auth::id();
        $notice->save();
    }

    public function render()
    {
        $notices = CaretakerScope::onTenantChild(NoticeToVacate::query())
            ->with('tenant')
            ->latest()
            ->get();

        return view('livewire.admin-app.notices', ['notices' => $notices])
            ->layout('components.layouts.app', ['title' => 'Notices']);
    }
}
