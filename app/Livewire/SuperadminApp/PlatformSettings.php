<?php

namespace App\Livewire\SuperadminApp;

use App\Helpers\SmsHelper;
use App\Models\Setting;
use App\Support\FaviconGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Platform-wide configuration (stored in the null-landlord "system" Setting row):
 * - App name/SMS/SMTP here act as the fallback used by any landlord who hasn't
 *   configured their own in their own Settings (see Setting::effective()).
 * - Subscription Billing (subscription_mpesa/subscription_pesapal) is unrelated to
 *   that fallback - it's the gateway used to charge LANDLORDS for their own Renty
 *   subscription, never a fallback for a landlord's tenant rent collection (each
 *   landlord's own mpesa/pesapal for that stays individual, set in their own Settings).
 */
class PlatformSettings extends Component
{
    use WithFileUploads;

    public string $activeTab = 'general';

    public array $data = [];

    public string $testSmsPhone = '';

    /** Temporary upload for the assistant avatar - moved to storage in save(). */
    public $aiAvatarUpload = null;

    /** Temporary upload for the site logo - moved to storage in save(). */
    public $logoUpload = null;

    /** Temporary upload for a dedicated favicon image - moved to storage in save(). */
    public $faviconUpload = null;

    public function mount(): void
    {
        $payload = Setting::forLandlord(null)->payload ?? [];

        $defaults = [
            'app_name' => config('app.name'),
            'brand_palette' => 'green',
            'smtp' => ['encryption' => 'tls'],
            'subscription_mpesa' => ['sandbox' => true, 'currency' => 'KES'],
            'subscription_pesapal' => ['sandbox' => true, 'currency' => 'KES'],
            'ai_search_enabled' => true,
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
            'data.openrouter_api_key' => 'nullable|string|max:255',
            'data.openrouter_model' => 'nullable|string|max:255',
            'data.ai_search_enabled' => 'nullable|boolean',
            'aiAvatarUpload' => 'nullable|image|max:2048',
            'logoUpload' => 'nullable|image|max:2048',
            'faviconUpload' => 'nullable|image|max:2048',
        ]);

        if ($this->aiAvatarUpload) {
            if (! empty($this->data['ai_avatar_path'])) {
                Storage::disk('public')->delete($this->data['ai_avatar_path']);
            }

            $this->data['ai_avatar_path'] = $this->aiAvatarUpload->store('branding', 'public');
            $this->aiAvatarUpload = null;
        }

        if ($this->logoUpload) {
            if (! empty($this->data['logo_path'])) {
                Storage::disk('public')->delete($this->data['logo_path']);
            }

            $this->data['logo_path'] = $this->logoUpload->store('branding', 'public');
            $this->logoUpload = null;

            // A dedicated favicon (below) always wins - only fall back to the
            // logo for the favicon when no dedicated favicon is set.
            if (empty($this->data['favicon_path'])) {
                FaviconGenerator::generate($this->data['logo_path']);
            }
        }

        if ($this->faviconUpload) {
            if (! empty($this->data['favicon_path'])) {
                Storage::disk('public')->delete($this->data['favicon_path']);
            }

            $this->data['favicon_path'] = $this->faviconUpload->store('branding', 'public');
            $this->faviconUpload = null;

            FaviconGenerator::generate($this->data['favicon_path']);
        }

        $settings = Setting::forLandlord(null);
        $settings->payload = array_replace_recursive($settings->payload ?? [], $this->data);
        $settings->save();

        $this->data = $settings->payload;
        session()->flash('platform-settings-saved', 'Platform settings saved.');
    }

    public function removeLogo(): void
    {
        if (! empty($this->data['logo_path'])) {
            Storage::disk('public')->delete($this->data['logo_path']);
        }

        $this->data['logo_path'] = null;
        $this->logoUpload = null;

        $settings = Setting::forLandlord(null);
        $settings->payload = array_replace_recursive($settings->payload ?? [], $this->data);
        $settings->save();

        session()->flash('platform-settings-saved', 'Site logo removed - back to the text logo.');
    }

    public function removeFavicon(): void
    {
        if (! empty($this->data['favicon_path'])) {
            Storage::disk('public')->delete($this->data['favicon_path']);
        }

        $this->data['favicon_path'] = null;
        $this->faviconUpload = null;

        $settings = Setting::forLandlord(null);
        $settings->payload = array_replace_recursive($settings->payload ?? [], $this->data);
        $settings->save();

        // Falls back to the logo-derived favicon, if a logo is set - otherwise
        // the favicon files on disk stay as whatever was last generated.
        if (! empty($this->data['logo_path'])) {
            FaviconGenerator::generate($this->data['logo_path']);
        }

        session()->flash('platform-settings-saved', 'Dedicated favicon removed.');
    }

    public function removeAiAvatar(): void
    {
        if (! empty($this->data['ai_avatar_path'])) {
            Storage::disk('public')->delete($this->data['ai_avatar_path']);
        }

        $this->data['ai_avatar_path'] = null;
        $this->aiAvatarUpload = null;

        $settings = Setting::forLandlord(null);
        $settings->payload = array_replace_recursive($settings->payload ?? [], $this->data);
        $settings->save();

        session()->flash('platform-settings-saved', 'Assistant avatar removed.');
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

    /**
     * Sends a tiny chat completion using whatever's currently typed above (not the
     * last saved values), same as sendTestSms() above - mirrors the equivalent
     * "Test Connection" action on the Filament panel's Platform Settings page.
     */
    public function testOpenRouter(): void
    {
        $apiKey = trim((string) ($this->data['openrouter_api_key'] ?? ''));
        $model = trim((string) ($this->data['openrouter_model'] ?? '')) ?: 'meta-llama/llama-3.1-8b-instruct:free';

        if ($apiKey === '') {
            session()->flash('platform-settings-error', 'Enter an API key first.');
            return;
        }

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders([
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('app.name'),
                ])
                ->timeout(20)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Reply with exactly one word: OK'],
                    ],
                    'max_tokens' => 5,
                ]);

            if ($response->successful()) {
                $reply = trim((string) ($response->json('choices.0.message.content') ?? ''));
                session()->flash('platform-settings-saved', "Connection works - model \"{$model}\" replied: \"{$reply}\"");
            } else {
                $error = $response->json('error.message') ?? $response->body();
                session()->flash('platform-settings-error', 'Connection failed (HTTP ' . $response->status() . '): ' . $error);
            }
        } catch (\Throwable $e) {
            session()->flash('platform-settings-error', 'Failed to reach OpenRouter: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.superadmin-app.platform-settings')
            ->layout('components.layouts.app', ['title' => 'Platform Settings']);
    }
}
