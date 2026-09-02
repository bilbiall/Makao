<?php

namespace App\Livewire\SuperadminApp;

use App\Helpers\SmsHelper;
use App\Models\Setting;
use Livewire\Component;

/**
 * Platform-wide configuration (stored in the null-landlord "system" Setting row):
 * - App name/SMS/SMTP here act as the fallback used by any landlord who hasn't
 *   configured their own in their own Settings (see Setting::effective()).
 * - Subscription Billing (subscription_mpesa/subscription_pesapal) is unrelated to
 *   that fallback - it's the gateway used to charge LANDLORDS for their own Makao
 *   subscription, never a fallback for a landlord's tenant rent collection (each
 *   landlord's own mpesa/pesapal for that stays individual, set in their own Settings).
 */
class PlatformSettings extends Component
{
    public string $activeTab = 'general';

    public array $data = [];

    public string $testSmsPhone = '';

    public function mount(): void
    {
        $payload = Setting::forLandlord(null)->payload ?? [];

        $defaults = [
            'app_name' => config('app.name'),
            'brand_palette' => 'green',
            'smtp' => ['encryption' => 'tls'],
            'subscription_mpesa' => ['sandbox' => true, 'currency' => 'KES'],
            'subscription_pesapal' => ['sandbox' => true, 'currency' => 'KES'],
        ];

        $this->data = array_replace_recursive($defaults, $payload);
    }

    public function save(): void
    {
        $this->validate([
            'data.app_name' => 'required|string|max:255',
            'data.google_analytics_id' => 'nullable|string|max:50',
            'data.platform_support_email' => 'nullable|email|max:255',
            'data.sms_url' => 'nullable|url|max:255',
            'data.sms_api_key' => 'nullable|string|max:255',
            'data.sms_partner_id' => 'nullable|string|max:100',
            'data.sms_sender_id' => 'nullable|string|max:50',
            'data.smtp.host' => 'nullable|string|max:255',
            'data.smtp.port' => 'nullable|numeric',
            'data.smtp.username' => 'nullable|string|max:255',
            'data.smtp.from_email' => 'nullable|email|max:255',
            'data.brand_palette' => 'required|in:' . implode(',', array_keys(\App\Support\BrandPalette::OPTIONS)),
        ]);

        $settings = Setting::forLandlord(null);
        $settings->payload = array_replace_recursive($settings->payload ?? [], $this->data);
        $settings->save();

        $this->data = $settings->payload;
        session()->flash('platform-settings-saved', 'Platform settings saved.');
    }

    /**
     * Sends using whatever is currently typed in the SMS tab (not the last saved
     * values), so superadmin can verify credentials work before saving them.
     */
    public function sendTestSms(): void
    {
        $phone = trim($this->testSmsPhone);

        if ($phone === '') {
            session()->flash('platform-settings-error', 'Enter a phone number first.');
            return;
        }

        try {
            SmsHelper::sendWithConfig($phone, 'This is a test message from ' . ($this->data['app_name'] ?? config('app.name')) . ' - your platform SMS settings are working.', [
                'sms_url' => $this->data['sms_url'] ?? null,
                'sms_api_key' => $this->data['sms_api_key'] ?? null,
                'sms_partner_id' => $this->data['sms_partner_id'] ?? null,
                'sms_sender_id' => $this->data['sms_sender_id'] ?? null,
            ]);

            session()->flash('platform-settings-saved', 'Test SMS sent - check the phone.');
        } catch (\Throwable $e) {
            session()->flash('platform-settings-error', 'Failed to send test SMS: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.superadmin-app.platform-settings')
            ->layout('components.layouts.app', ['title' => 'Platform Settings']);
    }
}
