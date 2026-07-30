<?php

namespace App\Livewire\SuperadminApp;

use App\Models\Setting;
use Livewire\Component;

class PlatformSettings extends Component
{
    public string $google_analytics_id = '';
    public string $platform_support_email = '';

    public function mount(): void
    {
        $payload = Setting::forLandlord(null)->payload ?? [];
        $this->google_analytics_id = $payload['google_analytics_id'] ?? '';
        $this->platform_support_email = $payload['platform_support_email'] ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'google_analytics_id' => 'nullable|string|max:50',
            'platform_support_email' => 'nullable|email|max:255',
        ]);

        $settings = Setting::forLandlord(null);
        $settings->payload = array_merge($settings->payload ?? [], [
            'google_analytics_id' => $this->google_analytics_id,
            'platform_support_email' => $this->platform_support_email,
        ]);
        $settings->save();

        session()->flash('platform-settings-saved', 'Platform settings saved.');
    }

    public function render()
    {
        return view('livewire.superadmin-app.platform-settings')
            ->layout('components.layouts.app', ['title' => 'Platform Settings']);
    }
}
