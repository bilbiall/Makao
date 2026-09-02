<?php

namespace App\Filament\Concerns;

use App\Helpers\PaymentGatewayRequestHelper;
use App\Helpers\SmsHelper;
use App\Models\Setting;
use Filament\Forms;
use Filament\Notifications\Notification;

/**
 * The full per-landlord settings form (SMS/email templates, SMTP, billing/auto-invoice,
 * payment mode, M-Pesa/Pesapal credentials). Shared by the landlord-facing Settings page
 * (App\Filament\Pages\Settings) and the superadmin's per-landlord settings view
 * (App\Filament\Superadmin\Resources\LandlordResource\Pages\ManageLandlordSettings), so a
 * superadmin can view/troubleshoot a landlord's own configuration without a second copy
 * of this schema to keep in sync.
 *
 * App name/SMS/SMTP setup and the auto-invoice cronjob guide are technical, platform-
 * facing configuration left to 'admin'/'superadmin' - a 'landlord' (the property owner)
 * gets those tabs/sections hidden entirely. Same split for M-Pesa/Pesapal credentials:
 * a landlord can't self-serve those, they request a gateway and admin/superadmin fills
 * the real credentials in from here (see PaymentGatewayRequestHelper).
 */
trait HasLandlordSettingsSchema
{
    /**
     * @param int|null $landlordId Defaults to the current user's own landlord_id - pass
     *  explicitly only from ManageLandlordSettings, where a superadmin is viewing a
     *  landlord other than themselves.
     */
    public static function landlordSettingsTabs(?int $landlordId = null): array
    {
        $landlordId ??= auth()->user()->landlord_id;
        $isAdminRole = in_array(auth()->user()->role, ['admin', 'superadmin'], true);
        $hasGatewayCredentials = Setting::forLandlord($landlordId)->hasPaymentGatewayCredentials();

        return [
            Forms\Components\Tabs\Tab::make('General')
                ->schema([
                    // The app_name field itself is admin/superadmin-only - everything
                    // else on this tab is onboarding-type business info a landlord
                    // keeps being able to update themselves.
                    Forms\Components\TextInput::make('app_name')
                        ->label('Application Name')
                        ->helperText('Display name for your application')
                        ->maxLength(255)
                        ->visible($isAdminRole)
                        ->required($isAdminRole),

                    Forms\Components\TextInput::make('company_name')
                        ->label('Company/Business Name')
                        ->helperText('Legal name of your business')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('support_email')
                        ->label('Support Email')
                        ->email()
                        ->helperText('Email address for customer support')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('support_phone')
                        ->label('Support Phone')
                        ->tel()
                        ->helperText('Phone number for customer support')
                        ->maxLength(50),

                    Forms\Components\Textarea::make('company_address')
                        ->label('Company Address')
                        ->helperText('Physical address of your business')
                        ->rows(3)
                        ->maxLength(500),

                    Forms\Components\Select::make('timezone')
                        ->label('Timezone')
                        ->options([
                            'Africa/Nairobi' => 'Africa/Nairobi (EAT)',
                            'Africa/Lagos' => 'Africa/Lagos (WAT)',
                            'Africa/Johannesburg' => 'Africa/Johannesburg (SAST)',
                            'UTC' => 'UTC',
                        ])
                        ->default('Africa/Nairobi')
                        ->searchable(),

                    Forms\Components\Select::make('currency')
                        ->label('Currency')
                        ->options([
                            'KES' => 'KES - Kenyan Shilling',
                            'USD' => 'USD - US Dollar',
                            'GBP' => 'GBP - British Pound',
                            'EUR' => 'EUR - Euro',
                        ])
                        ->default('KES')
                        ->searchable(),

                    Forms\Components\Textarea::make('terms_conditions')
                        ->label('Terms & Conditions')
                        ->helperText('Terms and conditions for tenants (optional)')
                        ->rows(5)
                        ->maxLength(5000),
                ])
                ->columns(2),

            Forms\Components\Tabs\Tab::make('SMS')
                ->visible($isAdminRole)
                ->schema([
                    Forms\Components\TextInput::make('sms_url')
                        ->label('SMS API URL')
                        ->url()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('sms_api_key')
                        ->label('SMS API Key')
                        ->password()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('sms_partner_id')
                        ->label('SMS Partner ID')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('sms_sender_id')
                        ->label('SMS Sender ID')
                        ->maxLength(50),

                    Forms\Components\Section::make('Send a test SMS')
                        ->description('Verify these credentials actually work before relying on them - sends using whatever is currently typed above, not the last saved values.')
                        ->schema([
                            Forms\Components\TextInput::make('test_sms_phone')
                                ->label('Phone number')
                                ->tel()
                                ->placeholder('e.g. 0712345678')
                                ->dehydrated(false),

                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('send_test_sms')
                                    ->label('Send test SMS')
                                    ->color('gray')
                                    ->action(function ($livewire) {
                                        // Read straight off the page's own $data (rather than the
                                        // Get utility) for the same reason as the gateway request
                                        // action below - not sensitive to nesting depth.
                                        $phone = trim((string) ($livewire->data['test_sms_phone'] ?? ''));

                                        if ($phone === '') {
                                            Notification::make()->danger()->title('Enter a phone number first')->send();
                                            return;
                                        }

                                        try {
                                            SmsHelper::sendWithConfig($phone, 'This is a test message from ' . ($livewire->data['app_name'] ?? config('app.name')) . ' - your SMS settings are working.', [
                                                'sms_url' => $livewire->data['sms_url'] ?? null,
                                                'sms_api_key' => $livewire->data['sms_api_key'] ?? null,
                                                'sms_partner_id' => $livewire->data['sms_partner_id'] ?? null,
                                                'sms_sender_id' => $livewire->data['sms_sender_id'] ?? null,
                                            ]);

                                            Notification::make()->success()->title('Test SMS sent - check the phone.')->send();
                                        } catch (\Throwable $e) {
                                            Notification::make()->danger()->title('Failed to send test SMS')->body($e->getMessage())->send();
                                        }
                                    }),
                            ]),
                        ]),
                ]),

            Forms\Components\Tabs\Tab::make('Templates')
                ->schema([
                    Forms\Components\Textarea::make('template_invoice')
                        ->label('Invoice Notification Template')
                        ->helperText('Variables: {tenant_name}, {invoice_number}, {amount}, {due_date}, {property_name}')
                        ->rows(4),

                    Forms\Components\Textarea::make('template_payment')
                        ->label('Payment Confirmation Template')
                        ->helperText('Variables: {tenant_name}, {amount_paid}, {invoice_number}, {balance}, {app_name}, {property_name}')
                        ->default('Hi {tenant_name}, we\'ve received your payment of KES {amount_paid} for Invoice #{invoice_number}. Your remaining balance is KES {balance}. Thank you. - {app_name}')
                        ->rows(4),

                    Forms\Components\Textarea::make('template_payment_reminder')
                        ->label('Payment Reminder Template')
                        ->helperText('Variables: {tenant_name}, {amount}, {due_date}')
                        ->rows(4),

                    Forms\Components\Textarea::make('template_mass_reminder')
                        ->label('Mass Reminder Template')
                        ->helperText('Variables: {tenant_name}, {invoice_number}, {amount}, {due_date}, {app_name}, {property_name}')
                        ->default('Hi {tenant_name}, this is a reminder for Invoice {invoice_number}: KES {amount} due by {due_date}. Thank you, {app_name}.')
                        ->rows(4),

                    Forms\Components\Textarea::make('template_issue_notification')
                        ->label('Issue Notification Template')
                        ->helperText('Variables: {tenant_name}, {issue_title}, {issue_description}')
                        ->rows(4),

                    Forms\Components\Textarea::make('template_tenant_welcome')
                        ->label('Tenant Welcome Template')
                        ->helperText('Variables: {tenant_name}, {app_name}, {house_name}, {rent_amount}, {property_name}')
                        ->default('Hello {tenant_name}, welcome to {app_name}. You were admitted to {house_name} with a monthly rent of KES {rent_amount}')
                        ->rows(4),

                    Forms\Components\Textarea::make('template_notice_approved')
                        ->label('Notice Approved Template')
                        ->helperText('Variables: {tenant_name}, {balance}, {approval_date}, {vacate_date}, {property_name}')
                        ->default('Hi {tenant_name}, your vacate notice has been approved. Balance: KES {balance}. Approval date: {approval_date}. Vacate date: {vacate_date}.')
                        ->rows(4),

                    Forms\Components\Textarea::make('template_notice_denied')
                        ->label('Notice Denied Template')
                        ->helperText('Variables: {tenant_name}, {balance}, {vacate_date}, {property_name}')
                        ->default('Hi {tenant_name}, your vacate notice has been denied. Balance: KES {balance}. Date requested: {vacate_date}.')
                        ->rows(4),

                    Forms\Components\Textarea::make('template_password_reset_sms')
                        ->label('Password Reset SMS Template')
                        ->helperText('Variables: {tenant_name}, {reset_code}, {app_name}')
                        ->default('Hi {tenant_name}, use this code to reset your password: {reset_code}. - {app_name}')
                        ->rows(3),

                    Forms\Components\Textarea::make('template_new_user_sms')
                        ->label('New User Registration SMS Template')
                        ->helperText('Variables: {user_name}, {email}, {password}, {role}, {site_url}, {app_name}')
                        ->default('Hi {user_name}, your {role} account has been created. Email: {email} | Password: {password} | Login: {site_url} - {app_name}')
                        ->rows(4),
                ]),

            Forms\Components\Tabs\Tab::make('Email')
                ->visible($isAdminRole)
                ->schema([
                    Forms\Components\Section::make('SMTP')
                        ->schema([
                            Forms\Components\TextInput::make('smtp.host')
                                ->label('SMTP Host')
                                ->placeholder('smtp.example.com')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('smtp.port')
                                ->label('SMTP Port')
                                ->numeric()
                                ->placeholder('587'),

                            Forms\Components\Select::make('smtp.encryption')
                                ->label('Encryption')
                                ->options([
                                    'tls' => 'TLS (STARTTLS - typically port 587)',
                                    'ssl' => 'SSL (implicit TLS - typically port 465)',
                                    'none' => 'None',
                                ])
                                ->default('tls'),

                            Forms\Components\TextInput::make('smtp.username')
                                ->label('SMTP Username')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('smtp.password')
                                ->label('SMTP Password')
                                ->password()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('smtp.from_email')
                                ->label('From Email')
                                ->email()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('smtp.from_name')
                                ->label('From Name')
                                ->maxLength(255),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Email Templates')
                        ->schema([
                            Forms\Components\Textarea::make('email_template_message')
                                ->label('Chat Message Email Template')
                                ->helperText('Variables: {tenant_name}, {sender_name}, {message_body}, {app_name}')
                                ->default("Hi {tenant_name},\n\nYou have a new message from {sender_name}:\n{message_body}\n\nRegards, {app_name}")
                                ->rows(4),

                            Forms\Components\Textarea::make('email_template_notice_approved')
                                ->label('Notice Approved Email Template')
                                ->helperText('Variables: {tenant_name}, {house_name}, {vacate_date}, {balance}, {app_name}')
                                ->default("Hi {tenant_name}, your notice to vacate {house_name} on {vacate_date} has been approved. Balance: KES {balance}.\n\nRegards, {app_name}")
                                ->rows(4),

                            Forms\Components\Textarea::make('email_template_notice_denied')
                                ->label('Notice Denied Email Template')
                                ->helperText('Variables: {tenant_name}, {house_name}, {vacate_date}, {reason}, {app_name}')
                                ->default("Hi {tenant_name}, your notice to vacate {house_name} on {vacate_date} has been denied. {reason}\n\nRegards, {app_name}")
                                ->rows(4),

                            Forms\Components\Textarea::make('email_template_password_reset')
                                ->label('Password Reset Email Template')
                                ->helperText('Variables: {tenant_name}, {reset_url}, {reset_code}, {app_name}')
                                ->default("Hi {tenant_name},\n\nYou requested a password reset. Click the link or use the code below:\n{reset_url}\nReset Code: {reset_code}\n\nIf you did not request this, please ignore this email.\n\nRegards, {app_name}")
                                ->rows(6),

                            Forms\Components\Textarea::make('email_template_new_user')
                                ->label('New User Registration Email Template')
                                ->helperText('Variables: {user_name}, {email}, {password}, {role}, {site_url}, {app_name}')
                                ->default("Hi {user_name},\n\nYour {role} account has been successfully created!\n\nLogin Details:\nEmail: {email}\nPassword: {password}\n\nYou can login at: {site_url}\n\nPlease change your password after first login.\n\nRegards,\n{app_name}")
                                ->rows(8),
                        ])
                        ->columns(2),
                    // Future SMTP settings
                ]),

            Forms\Components\Tabs\Tab::make('Billing')
                ->schema([
                    Forms\Components\DatePicker::make('auto_invoice_date')
                        ->label('Auto Invoice Date (Monthly)')
                        ->helperText('Day of month (1-31) when invoices are automatically sent. Leave empty to disable.')
                        ->native(false),

                    Forms\Components\Toggle::make('auto_invoice_enabled')
                        ->label('Enable Automatic Invoicing')
                        ->default(false),

                    Forms\Components\Placeholder::make('cronjob_guide')
                        ->label('Cronjob Setup Guide')
                        ->visible($isAdminRole)
                        ->content(new \Illuminate\Support\HtmlString('
                            <div class="text-sm space-y-4">
                                <p class="font-semibold text-gray-700 dark:text-gray-300">
                                    For automatic invoicing to work, you need to set up a cronjob on your server.
                                </p>

                                <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-md">
                                    <p class="font-mono text-xs break-all">
                                        * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300">Step-by-Step Guide for cPanel:</p>

                                    <ol class="list-decimal list-inside space-y-2 text-gray-600 dark:text-gray-400">
                                        <li>
                                            <strong>Login to cPanel</strong><br>
                                            <span class="ml-6 text-xs">Access your hosting cPanel account.</span>
                                        </li>

                                        <li>
                                            <strong>Find Cron Jobs</strong><br>
                                            <span class="ml-6 text-xs">In the "Advanced" section, click on "Cron Jobs".</span>
                                        </li>

                                        <li>
                                            <strong>Add New Cron Job</strong><br>
                                            <span class="ml-6 text-xs">Scroll down to "Add New Cron Job" section.</span>
                                        </li>

                                        <li>
                                            <strong>Set the Schedule</strong><br>
                                            <span class="ml-6 text-xs">Select "Common Settings" dropdown and choose "Every Minute (* * * * *)" or manually set all fields to asterisk (*).</span>
                                        </li>

                                        <li>
                                            <strong>Enter the Command</strong><br>
                                            <span class="ml-6 text-xs">In the "Command" field, enter:</span><br>
                                            <code class="ml-6 text-xs bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded block mt-1">
                                                cd /home/yourusername/public_html && php artisan schedule:run >> /dev/null 2>&1
                                            </code><br>
                                            <span class="ml-6 text-xs italic">Replace <strong>/home/yourusername/public_html</strong> with your actual application path.</span><br><br>
                                            <div class="ml-6 mt-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-2 rounded">
                                                <p class="text-xs font-semibold text-green-800 dark:text-green-300 mb-1">Example for renty.co.ke:</p>
                                                <p class="text-xs text-green-700 dark:text-green-400 mb-2">
                                                    If your cPanel username is <strong>rentyke</strong> and Laravel is in your root public_html:
                                                </p>
                                                <code class="text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded block border border-green-300 dark:border-green-700">
                                                    cd /home/rentyke/public_html && php artisan schedule:run >> /dev/null 2>&1
                                                </code>
                                                <p class="text-xs text-green-700 dark:text-green-400 mt-2">
                                                    Or if in a subfolder like <strong>public_html/renty</strong>:
                                                </p>
                                                <code class="text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded block border border-green-300 dark:border-green-700">
                                                    cd /home/rentyke/public_html/renty && php artisan schedule:run >> /dev/null 2>&1
                                                </code>
                                            </div>
                                        </li>

                                        <li>
                                            <strong>Add Cron Job</strong><br>
                                            <span class="ml-6 text-xs">Click the "Add New Cron Job" button to save.</span>
                                        </li>

                                        <li>
                                            <strong>Verify Setup</strong><br>
                                            <span class="ml-6 text-xs">The cronjob will now run every minute. Laravel\'s scheduler will determine which tasks to execute based on your schedule configuration.</span>
                                        </li>
                                    </ol>

                                    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-3 mt-4">
                                        <p class="text-xs text-blue-700 dark:text-blue-300">
                                            <strong>Note:</strong> The cronjob runs every minute, but Laravel\'s scheduler only executes tasks at their scheduled time. Your automatic invoices will be sent on the date you specified above.
                                        </p>
                                    </div>

                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-3 mt-2">
                                        <p class="text-xs text-yellow-700 dark:text-yellow-300">
                                            <strong>Finding Your Path:</strong> If unsure of your application path, run <code>pwd</code> command in SSH terminal while in your application directory, or check the "File Manager" in cPanel.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ')),
                ]),

            Forms\Components\Tabs\Tab::make('Payments')
                ->schema([
                    Forms\Components\Section::make('Manual payment details')
                        ->description('Shown to tenants paying you directly - bank account, paybill, or till number.')
                        ->schema([
                            Forms\Components\TextInput::make('manual_payment.bank_name')
                                ->label('Bank name')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('manual_payment.account_name')
                                ->label('Account name')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('manual_payment.account_number')
                                ->label('Account number')
                                ->maxLength(100),

                            Forms\Components\TextInput::make('manual_payment.paybill_number')
                                ->label('Paybill number')
                                ->maxLength(100),

                            Forms\Components\TextInput::make('manual_payment.till_number')
                                ->label('Till number')
                                ->maxLength(100),

                            Forms\Components\Textarea::make('manual_payment.instructions')
                                ->label('Other instructions')
                                ->placeholder('e.g. use your house number as the M-Pesa reference')
                                ->rows(2),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Collection Method')
                        ->description('Choose how rent payments are collected from tenants.')
                        ->schema([
                            Forms\Components\Radio::make('payment_mode')
                                ->label('Payment Mode')
                                ->options(fn () => $hasGatewayCredentials || $isAdminRole ? [
                                    'manual' => 'Manual - tenants pay you directly (details above) and you record it yourself',
                                    'automatic' => 'Automatic - tenants pay in-app via M-Pesa STK push / Pesapal',
                                ] : [
                                    'manual' => 'Manual - tenants pay you directly (details above) and you record it yourself',
                                ])
                                ->descriptions([
                                    'manual' => 'The tenant portal will not show M-Pesa/Pesapal pay buttons. Use this if you are not ready to connect a payment gateway yet.',
                                    'automatic' => 'Requires M-Pesa (Daraja) and/or Pesapal credentials, set up below.',
                                ])
                                ->default('manual')
                                ->inline(false)
                                ->required(),
                        ]),

                    Forms\Components\Section::make('Request automatic payments')
                        ->description('This needs real M-Pesa/Pesapal business credentials - request it and our team sets it up for you.')
                        ->visible(!$isAdminRole && !$hasGatewayCredentials)
                        ->schema([
                            Forms\Components\Placeholder::make('gateway_request_status')
                                ->label('')
                                ->visible(fn () => (bool) Setting::forLandlord($landlordId)->pendingPaymentGatewayRequest())
                                ->content(function () use ($landlordId) {
                                    $request = Setting::forLandlord($landlordId)->pendingPaymentGatewayRequest();
                                    if (!$request) {
                                        return '';
                                    }
                                    $when = \Illuminate\Support\Carbon::parse($request['requested_at'])->diffForHumans();
                                    $label = PaymentGatewayRequestHelper::methodLabel($request['method']);

                                    return new \Illuminate\Support\HtmlString(
                                        "<div class=\"text-sm text-amber-700\">Request sent - {$label}. Submitted {$when}. Our team will set this up and notify you once it's ready.</div>"
                                    );
                                }),

                            Forms\Components\Select::make('gateway_request_method')
                                ->label('Which gateway?')
                                ->options(['mpesa' => 'M-Pesa', 'pesapal' => 'Pesapal', 'both' => 'Both'])
                                ->default('mpesa')
                                ->dehydrated(false)
                                ->visible(fn () => !Setting::forLandlord($landlordId)->pendingPaymentGatewayRequest()),

                            Forms\Components\Textarea::make('gateway_request_note')
                                ->label('Note (optional)')
                                ->rows(2)
                                ->dehydrated(false)
                                ->visible(fn () => !Setting::forLandlord($landlordId)->pendingPaymentGatewayRequest()),

                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('request_gateway')
                                    ->label('Request automatic payment setup')
                                    ->visible(fn () => !Setting::forLandlord($landlordId)->pendingPaymentGatewayRequest())
                                    ->action(function ($livewire) use ($landlordId) {
                                        // Read straight off the page's own $data (rather than the
                                        // Get utility) so this isn't sensitive to how deeply this
                                        // Action sits nested inside its Actions/Section wrapper.
                                        PaymentGatewayRequestHelper::submit(
                                            $landlordId,
                                            $livewire->data['gateway_request_method'] ?? 'mpesa',
                                            $livewire->data['gateway_request_note'] ?? null,
                                            auth()->id(),
                                        );

                                        $livewire->form->fill(array_replace_recursive(
                                            $livewire->form->getState(),
                                            Setting::forLandlord($landlordId)->payload,
                                        ));

                                        Notification::make()
                                            ->title('Request sent - our team will follow up.')
                                            ->success()
                                            ->send();
                                    }),
                            ]),
                        ]),

                    Forms\Components\Section::make('Pending request')
                        ->visible(fn () => $isAdminRole && Setting::forLandlord($landlordId)->pendingPaymentGatewayRequest())
                        ->schema([
                            Forms\Components\Placeholder::make('pending_gateway_request')
                                ->label('')
                                ->content(function () use ($landlordId) {
                                    $request = Setting::forLandlord($landlordId)->pendingPaymentGatewayRequest();
                                    if (!$request) {
                                        return '';
                                    }
                                    $when = \Illuminate\Support\Carbon::parse($request['requested_at'])->diffForHumans();
                                    $label = PaymentGatewayRequestHelper::methodLabel($request['method']);
                                    $note = $request['note'] ?? null;

                                    $html = "<div class=\"text-sm text-amber-700 space-y-1\">"
                                        . "<p class=\"font-semibold\">Property owner requested {$label}</p>"
                                        . ($note ? '<p>"' . e($note) . '"</p>' : '')
                                        . "<p>Requested {$when}. Fill in the credentials below, set Collection Method to Automatic, then Save to mark this fulfilled.</p>"
                                        . '</div>';

                                    return new \Illuminate\Support\HtmlString($html);
                                }),
                        ]),

                    // Pesapal Settings
                    Forms\Components\Section::make('Pesapal')
                        ->visible($isAdminRole)
                        ->schema([
                            Forms\Components\TextInput::make('pesapal.consumer_key')
                                ->label('Pesapal Consumer Key')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('pesapal.consumer_secret')
                                ->label('Pesapal Consumer Secret')
                                ->password()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('pesapal.webhook_secret')
                                ->label('Pesapal Webhook Secret')
                                ->password()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('pesapal.ipn_id')
                                ->label('Pesapal IPN ID')
                                ->helperText('Register via Pesapal API: POST /api/3/notification-urls. Sandbox and Live have separate IPN IDs.')
                                ->placeholder('e.g., a12b34cd-5678-90ef-aaaa-bbbbccccdddd')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('pesapal.callback_url')
                                ->label('Pesapal Callback URL')
                                ->helperText('Public webhook/callback URL Pesapal will call (e.g., https://example.com/api/pesapal/webhook)')
                                ->url()
                                ->maxLength(1024),

                            Forms\Components\Toggle::make('pesapal.sandbox')
                                ->label('Use Pesapal Sandbox')
                                ->default(true),

                            Forms\Components\TextInput::make('pesapal.currency')
                                ->label('Currency')
                                ->default('KES')
                                ->maxLength(10),
                        ]),

                    // M-Pesa Daraja Settings
                    Forms\Components\Section::make('M-Pesa (Daraja API)')
                        ->visible($isAdminRole)
                        ->schema([
                            Forms\Components\TextInput::make('mpesa.consumer_key')
                                ->label('Daraja API Key')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('mpesa.consumer_secret')
                                ->label('Daraja API Secret')
                                ->password()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('mpesa.business_shortcode')
                                ->label('Business Short Code')
                                ->placeholder('e.g., 174379')
                                ->maxLength(10),

                            Forms\Components\TextInput::make('mpesa.passkey')
                                ->label('M-Pesa Online Passkey')
                                ->password()
                                ->helperText('From your M-Pesa merchant dashboard')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('mpesa.callback_url')
                                ->label('M-Pesa Callback URL')
                                ->helperText('Public webhook/callback URL Safaricom will call (e.g., https://example.com/api/mpesa/callback)')
                                ->url()
                                ->maxLength(1024),

                            Forms\Components\Toggle::make('mpesa.sandbox')
                                ->label('Use Sandbox (Daraja Test)')
                                ->default(true),

                            Forms\Components\TextInput::make('mpesa.currency')
                                ->label('Currency')
                                ->default('KES')
                                ->maxLength(10),
                        ]),
                ]),
        ];
    }
}
