<?php

namespace App\Livewire\AdminApp;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Settings extends Component
{
    public string $payment_mode = 'manual';

    public function mount(): void
    {
        // Matches Settings::shouldRegisterNavigation() - caretakers don't manage
        // landlord-wide settings.
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);

        $payload = Setting::forLandlord(Auth::user()->landlord_id)->payload ?? [];
        $this->payment_mode = $payload['payment_mode'] ?? 'manual';
    }

    public function save(): void
    {
        $settings = Setting::forLandlord(Auth::user()->landlord_id);
        $settings->payload = array_merge($settings->payload ?? [], ['payment_mode' => $this->payment_mode]);
        $settings->save();

        session()->flash('settings-saved', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin-app.settings')
            ->layout('components.layouts.app', ['title' => 'Settings']);
    }
}
