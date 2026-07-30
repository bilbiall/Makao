<?php

namespace App\Livewire\Tenant;

use App\Models\NoticeToVacate;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notices extends Component
{
    public bool $showForm = false;
    public string $vacate_date = '';
    public string $reason_type = '';
    public string $reason_text = '';

    protected $rules = [
        'vacate_date' => 'required|date|after:today',
        'reason_type' => 'required|string',
        'reason_text' => 'nullable|string|max:1000',
    ];

    public function submit(): void
    {
        $this->validate();

        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            $this->addError('reason_type', 'No tenant record linked to your account.');
            return;
        }

        NoticeToVacate::create([
            'tenant_id' => $tenant->id,
            'vacate_date' => $this->vacate_date,
            'reason_type' => $this->reason_type,
            'reason_text' => $this->reason_text,
            'status' => 'pending',
        ]);

        $this->reset(['vacate_date', 'reason_type', 'reason_text', 'showForm']);
    }

    public function render()
    {
        $tenant = Auth::user()->tenant;

        $notices = $tenant
            ? NoticeToVacate::where('tenant_id', $tenant->id)->latest()->get()
            : collect();

        return view('livewire.tenant.notices', ['notices' => $notices])
            ->layout('components.layouts.app', ['title' => 'Notice to Vacate']);
    }
}
