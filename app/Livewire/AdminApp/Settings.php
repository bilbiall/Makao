<?php

namespace App\Livewire\AdminApp;

use App\Helpers\AppHelper;
use App\Helpers\EmailHelper;
use App\Helpers\PaymentGatewayRequestHelper;
use App\Helpers\SmsHelper;
use App\Helpers\SmsTemplateHelper;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Full parity with App\Filament\Pages\Settings (App\Filament\Concerns\HasLandlordSettingsSchema) -
 * the app-shell is the default UI for every role, so this can't be a trimmed summary
 * with a link out to Filament for the real functionality. Same underlying Setting
 * model/payload structure, hand-rolled as plain Blade/Livewire to match every other
 * page in this app-shell (no Filament form reuse).
 *
 * The 'admin' role (a landlord's own trusted staff) sees every tab, same as before.
 * The 'landlord' role (the property owner themselves) keeps General/Templates/
 * Billing/Payments - business info (company name, support contacts, timezone,
 * currency, terms) is onboarding-type info they can keep updating themselves. Only
 * the truly technical bits are admin/superadmin-only: the app_name field itself,
 * the whole SMS/Email(SMTP) tabs, and the cronjob guide (see isAdminRole()).
 */
class Settings extends Component
{
    public string $activeTab = 'general';

    public array $data = [];

    public string $gatewayRequestMethod = 'mpesa';
    public string $gatewayRequestNote = '';

    public string $testSmsPhone = '';

    public string $testEmailAddress = '';

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'landlord']), 403);

        $payload = Setting::forLandlord(Auth::user()->landlord_id)->payload ?? [];

        // Same defaults as HasLandlordSettingsSchema, applied only where not already set.
        $defaults = [
            'app_name' => config('app.name'),
            'timezone' => 'Africa/Nairobi',
            'currency' => 'KES',
            'template_invoice' => SmsTemplateHelper::getDefaultTemplate('template_invoice'),
            'template_payment' => SmsTemplateHelper::getDefaultTemplate('template_payment'),
            'template_payment_reminder' => SmsTemplateHelper::getDefaultTemplate('template_payment_reminder'),
            'template_issue_notification' => SmsTemplateHelper::getDefaultTemplate('template_issue_notification'),
            'template_mass_reminder' => SmsTemplateHelper::getDefaultTemplate('template_mass_reminder'),
            'template_tenant_welcome' => SmsTemplateHelper::getDefaultTemplate('template_tenant_welcome'),
            'template_tenant_invite' => SmsTemplateHelper::getDefaultTemplate('template_tenant_invite'),
            'template_notice_approved' => SmsTemplateHelper::getDefaultTemplate('template_notice_approved'),
            'template_notice_denied' => SmsTemplateHelper::getDefaultTemplate('template_notice_denied'),
            'template_password_reset_sms' => SmsTemplateHelper::getDefaultTemplate('template_password_reset_sms'),
            'template_new_user_sms' => SmsTemplateHelper::getDefaultTemplate('template_new_user_sms'),
            'email_template_message' => "Hi {tenant_name},\n\nYou have a new message from {sender_name}:\n{message_body}\n\nRegards, {app_name}",
            'email_template_notice_approved' => "Hi {tenant_name}, your notice to vacate {house_name} on {vacate_date} has been approved. Balance: KES {balance}.\n\nRegards, {app_name}",
            'email_template_notice_denied' => "Hi {tenant_name}, your notice to vacate {house_name} on {vacate_date} has been denied. {reason}\n\nRegards, {app_name}",
            'email_template_password_reset' => "Hi {tenant_name},\n\nYou requested a password reset. Click the link or use the code below:\n{reset_url}\nReset Code: {reset_code}\n\nIf you did not request this, please ignore this email.\n\nRegards, {app_name}",
            'email_template_new_user' => "Hi {user_name},\n\nYour {role} account has been successfully created!\n\nLogin Details:\nEmail: {email}\nPassword: {password}\n\nYou can login at: {site_url}\n\nPlease change your password after first login.\n\nRegards,\n{app_name}",
            'auto_invoice_enabled' => false,
            'payment_mode' => 'manual',
            'manual_payment' => [
                'bank_name' => '',
                'account_name' => '',
                'account_number' => '',
                'paybill_number' => '',
                'till_number' => '',
                'instructions' => '',
            ],
            'smtp' => ['encryption' => 'tls'],
            'pesapal' => ['sandbox' => true, 'currency' => 'KES'],
            'mpesa' => ['sandbox' => true, 'currency' => 'KES'],
        ];

        $this->data = array_replace_recursive($defaults, $payload);

        // Landlord role never sees these tabs (see isAdminRole()) - if one was
        // deep-linked or left over from a previous session, fall back to a tab
        // that's actually visible.
        if (!$this->isAdminRole() && in_array($this->activeTab, ['sms', 'email'], true)) {
            $this->activeTab = 'templates';
        }
    }

    /** The 'admin' staff role (and, via the Filament mirror of this page,
     *  'superadmin') - as opposed to 'landlord', the property owner themselves. */
    public function isAdminRole(): bool
    {
        return Auth::user()->isAdmin();
    }

    public function hasPaymentGatewayCredentials(): bool
    {
        return filled($this->data['mpesa']['consumer_key'] ?? null)
            || filled($this->data['pesapal']['consumer_key'] ?? null);
    }

    public function getPendingGatewayRequestProperty(): ?array
    {
        $request = $this->data['payment_gateway_request'] ?? null;

        return ($request && ($request['status'] ?? null) === 'pending') ? $request : null;
    }

    public function requestAutomaticPaymentSetup(): void
    {
        $this->validate([
            'gatewayRequestMethod' => 'required|in:mpesa,pesapal,both',
            'gatewayRequestNote' => 'nullable|string|max:500',
        ]);

        PaymentGatewayRequestHelper::submit(
            Auth::user()->landlord_id,
            $this->gatewayRequestMethod,
            $this->gatewayRequestNote ?: null,
            Auth::id(),
        );

        $this->data['payment_gateway_request'] = Setting::forLandlord(Auth::user()->landlord_id)->payload['payment_gateway_request'];
        $this->gatewayRequestNote = '';
        session()->flash('settings-saved', 'Request sent - our team will set this up and notify you.');
    }

    /**
     * Sends using whatever is currently typed in the SMS tab (not the last saved
     * values), so admin can verify credentials work before saving them.
     */
    public function sendTestSms(): void
    {
        $phone = trim($this->testSmsPhone);

        if ($phone === '') {
            session()->flash('settings-error', 'Enter a phone number first.');
            return;
        }

        try {
            SmsHelper::sendWithConfig($phone, 'This is a test message from ' . ($this->data['app_name'] ?? AppHelper::getAppName()) . ' - your SMS settings are working.', [
                'sms_url' => $this->data['sms_url'] ?? null,
                'sms_api_key' => $this->data['sms_api_key'] ?? null,
                'sms_partner_id' => $this->data['sms_partner_id'] ?? null,
                'sms_sender_id' => $this->data['sms_sender_id'] ?? null,
            ]);

            session()->flash('settings-saved', 'Test SMS sent - check the phone.');
        } catch (\Throwable $e) {
            session()->flash('settings-error', 'Failed to send test SMS: ' . $e->getMessage());
        }
    }

    /**
     * Sends using whatever is currently typed in the Email tab (not the last saved
     * values), so admin can verify SMTP credentials work before saving them.
     */
    public function sendTestEmail(): void
    {
        $email = trim($this->testEmailAddress);

        if ($email === '') {
            session()->flash('settings-error', 'Enter an email address first.');
            return;
        }

        try {
            EmailHelper::sendWithConfig(
                $email,
                'Test email from ' . ($this->data['app_name'] ?? AppHelper::getAppName()),
                'This is a test message - your SMTP settings are working.',
                [
                    'host' => $this->data['smtp']['host'] ?? null,
                    'port' => $this->data['smtp']['port'] ?? null,
                    'encryption' => $this->data['smtp']['encryption'] ?? null,
                    'username' => $this->data['smtp']['username'] ?? null,
                    'password' => $this->data['smtp']['password'] ?? null,
                    'from_email' => $this->data['smtp']['from_email'] ?? null,
                    'from_name' => $this->data['smtp']['from_name'] ?? null,
                ]
            );

            session()->flash('settings-saved', 'Test email sent - check the inbox (and spam folder).');
        } catch (\Throwable $e) {
            session()->flash('settings-error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    public function save(): void
    {
        $settings = Setting::forLandlord(Auth::user()->landlord_id);

        // A landlord can't touch admin-only fields even via a tampered request -
        // merge onto the existing payload instead of trusting $this->data wholesale
        // for anything outside what their own form actually exposes.
        $payload = $settings->payload ?? [];
        $incoming = $this->data;

        if (!$this->isAdminRole()) {
            foreach (['app_name', 'sms_url', 'sms_api_key', 'sms_partner_id', 'sms_sender_id', 'smtp', 'mpesa', 'pesapal'] as $lockedKey) {
                unset($incoming[$lockedKey]);
            }

            // A landlord may flip between manual/automatic once a gateway is
            // actually configured, but can't be the one to configure it.
            if (!$settings->hasPaymentGatewayCredentials()) {
                unset($incoming['payment_mode']);
            }
        }

        $payload = array_replace_recursive($payload, $incoming);

        // Once admin/superadmin has actually configured a gateway and flips this
        // live, the landlord's own pending request (if any) is fulfilled.
        if ($this->isAdminRole() && ($incoming['payment_mode'] ?? null) === 'automatic') {
            $payload['payment_gateway_request']['status'] = 'fulfilled';
        }

        $settings->payload = $payload;
        $settings->save();

        $this->data = $payload;
        session()->flash('settings-saved', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin-app.settings')
            ->layout('components.layouts.app', ['title' => 'Settings']);
    }
}
