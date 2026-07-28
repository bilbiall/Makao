<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $slug = 'settings';
    protected static ?string $navigationGroup = 'My Records';
    protected static ?string $title = 'System Settings';

    protected static string $view = 'filament.pages.settings';

    /**
     * Holds form state
     */
    public ?array $data = [];

    /**
     * Role-based access: Only admin can access Settings.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && $user->role === 'admin';
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || $user->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $settings = Setting::singleton();
        $payload = $settings->payload ?? [];

        // Set defaults from config if not already set
        if (empty($payload['app_name'])) {
            $payload['app_name'] = config('app.name');
        }

        // Fill the form with decoded payload
        $this->form->fill($payload);
    }


    /**
     * Define the form schema
     */
    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->schema([
                                Forms\Components\TextInput::make('app_name')
                                    ->label('Application Name')
                                    ->helperText('Display name for your application')
                                    ->maxLength(255)
                                    ->required(),

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
                            ]),

                        Forms\Components\Tabs\Tab::make('Templates')
                            ->schema([
                                Forms\Components\Textarea::make('template_invoice')
                                    ->label('Invoice Notification Template')
                                    ->helperText('Variables: {tenant_name}, {invoice_number}, {amount}, {due_date}')
                                    ->rows(4),

                                    Forms\Components\Textarea::make('template_payment')
                                        ->label('Payment Confirmation Template')
                                        ->helperText('Variables: {tenant_name}, {amount_paid}, {invoice_number}, {balance}, {app_name}')
                                        ->default('Hi {tenant_name}, we\'ve received your payment of KES {amount_paid} for Invoice #{invoice_number}. Your remaining balance is KES {balance}. Thank you. - {app_name}')
                                        ->rows(4),

                                Forms\Components\Textarea::make('template_payment_reminder')
                                    ->label('Payment Reminder Template')
                                    ->helperText('Variables: {tenant_name}, {amount}, {due_date}')
                                    ->rows(4),

                                Forms\Components\Textarea::make('template_mass_reminder')
                                    ->label('Mass Reminder Template')
                                    ->helperText('Variables: {tenant_name}, {invoice_number}, {amount}, {due_date}, {app_name}')
                                    ->default('Hi {tenant_name}, this is a reminder for Invoice {invoice_number}: KES {amount} due by {due_date}. Thank you, {app_name}.')
                                    ->rows(4),

                                Forms\Components\Textarea::make('template_issue_notification')
                                    ->label('Issue Notification Template')
                                    ->helperText('Variables: {tenant_name}, {issue_title}, {issue_description}')
                                    ->rows(4),

                                Forms\Components\Textarea::make('template_tenant_welcome')
                                    ->label('Tenant Welcome Template')
                                    ->helperText('Variables: {tenant_name}, {app_name}, {house_name}, {rent_amount}')
                                    ->default('Hello {tenant_name}, welcome to {app_name}. You were admitted to {house_name} with a monthly rent of KES {rent_amount}')
                                    ->rows(4),

                                Forms\Components\Textarea::make('template_notice_approved')
                                    ->label('Notice Approved Template')
                                    ->helperText('Variables: {tenant_name}, {balance}, {approval_date}, {vacate_date}')
                                    ->default('Hi {tenant_name}, your vacate notice has been approved. Balance: KES {balance}. Approval date: {approval_date}. Vacate date: {vacate_date}.')
                                    ->rows(4),

                                   Forms\Components\Textarea::make('template_notice_denied')
                                    ->label('Notice Denied Template')
                                    ->helperText('Variables: {tenant_name}, {balance}, {vacate_date}')
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
                                                    'tls' => 'TLS',
                                                    'ssl' => 'SSL',
                                                    'none' => 'None',
                                                ])
                                                ->default('tls'),

                                            Forms\Components\TextInput::make('smtp.username')
                                                ->label('SMTP Username')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('smtp.password')
                                                ->label('SMTP Password')
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('smtp.from_email')
                                                ->label('From Email')
                                                ->email()
                                                ->placeholder('no-reply@example.com'),

                                            Forms\Components\TextInput::make('smtp.from_name')
                                                ->label('From Name')
                                                ->placeholder(config('app.name')),
                                        ])
                                        ->columns(2),

                                    Forms\Components\Section::make('Email Templates')
                                        ->schema([
                                            Forms\Components\Textarea::make('email_template_message')
                                                ->label('Message Email Template')
                                                ->helperText('Variables: {tenant_name}, {sender_name}, {message_body}, {app_name}')
                                                ->default("Hi {tenant_name}, you have a new message from {sender_name}:\n\n{message_body}\n\nRegards, {app_name}")
                                                ->rows(5),

                                            Forms\Components\Textarea::make('email_template_invoice')
                                                ->label('Invoice Email Template')
                                                ->helperText('Variables: {tenant_name}, {invoice_number}, {amount}, {due_date}, {app_name}')
                                                ->default("Hi {tenant_name}, invoice {invoice_number} of KES {amount} is due by {due_date}.\n\nRegards, {app_name}")
                                                ->rows(4),

                                            Forms\Components\Textarea::make('email_template_payment')
                                                ->label('Payment Email Template')
                                                ->helperText('Variables: {tenant_name}, {amount_paid}, {invoice_number}, {balance}, {app_name}')
                                                ->default("Hi {tenant_name}, we received your payment of KES {amount_paid} for Invoice {invoice_number}. Remaining balance: KES {balance}.\n\nThank you, {app_name}")
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
                                                                Or if in a subfolder like <strong>public_html/makao</strong>:
                                                            </p>
                                                            <code class="text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded block border border-green-300 dark:border-green-700">
                                                                cd /home/rentyke/public_html/makao && php artisan schedule:run >> /dev/null 2>&1
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
                                // Pesapal Settings
                                Forms\Components\Section::make('Pesapal')
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
                    ]),
            ]);
    }

    /**
     * Persist settings
     */
    public function save(): void
    {
        $settings = Setting::singleton();

        // Store all form data inside the 'payload' JSON column
        $settings->payload = $this->form->getState();
        $settings->save();

        cache()->forget('settings_singleton');

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

}
