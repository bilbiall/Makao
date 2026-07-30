<?php

namespace App\Livewire\Tenant;

use App\Models\Issue;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Issues extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public string $title = '';
    public string $description = '';

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'required|string|max:2000',
    ];

    public function report(): void
    {
        $this->validate();

        $tenant = Auth::user()->tenant;

        if (!$tenant) {
            $this->addError('title', 'No tenant record linked to your account.');
            return;
        }

        Issue::create([
            'tenant_id' => $tenant->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => 'open',
        ]);

        $this->reset(['title', 'description', 'showForm']);
    }

    public function render()
    {
        $tenant = Auth::user()->tenant;

        $issues = $tenant
            ? Issue::where('tenant_id', $tenant->id)->latest()->paginate(10)
            : Issue::whereRaw('1 = 0')->paginate(10);

        return view('livewire.tenant.issues', ['issues' => $issues])
            ->layout('components.layouts.app', ['title' => 'Issues']);
    }
}
