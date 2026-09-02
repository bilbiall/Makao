<?php

namespace App\Filament\Superadmin\Pages;

use App\Helpers\SmsHelper;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Platform-wide configuration - not tied to any landlord (stored in the null-landlord
 * "system" Setting row), used for things that belong to the marketing site / platform
 * operator rather than any one tenant's business, e.g. analytics tracking.
 */
class PlatformSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Platform Settings';
    protected static ?string $slug = 'platform-settings';
    protected static ?string $title = 'Platform Settings';

    protected static string $view = 'filament.superadmin.pages.platform-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::forLandlord(null);
        $payload = $settings->payload ?? [];

        if (empty($payload['app_name'])) {
            $payload['app_name'] = config('app.name');
        }

        if (empty($payload['brand_palette'])) {
            $payload['brand_palette'] = 'green';
        }

        $this->form->fill($payload);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make('PlatformSettings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Appearance')
                            ->schema([
                                Forms\Components\Radio::make('brand_palette')
                                    ->label('Brand color')
                                    ->helperText('Re-skins the whole site - marketing pages, the mobile app-shell for every role, and every Filament panel (this one included). Takes effect on next page load.')
                                    ->options(\App\Support\BrandPalette::OPTIONS)
                                    ->descriptions([
                                        'green' => 'The current color - emerald green.',
                                        'blue' => 'A cool, trustworthy blue.',
                                        'gold' => 'A warm amber/gold.',
                                        'red' => 'A bold red (distinct from the rose/red already used for errors and delete actions).',
                                    ])
                                    ->default('green')
                                    ->required(),
                            ]),

                        Forms\Components\Tabs\Tab::make('General')
                            ->schema([
                                Forms\Components\TextInput::make('app_name')
                                    ->label('Application Name')
                                    ->helperText('Default display name used in tenant-facing messages for any landlord who hasn\'t set their own.')
                                    ->maxLength(255)
                                    ->required(),

                                Forms\Components\TextInput::make('google_analytics_id')
                                    ->label('Google Analytics Measurement ID')
                                    ->helperText('From Google Analytics (GA4): Admin > Data Streams > your stream. Looks like G-XXXXXXXXXX. Applies to the public marketing site only.')
                                    ->placeholder('G-XXXXXXXXXX')
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('platform_support_email')
                                    ->label('Platform Support Email')
                                    ->email()
                                    ->helperText('Shown on the marketing site for general enquiries (not a specific landlord\'s support contact).')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Tabs\Tab::make('SMS')
                            ->schema([
                                Forms\Components\Placeholder::make('sms_fallback_note')
                                    ->label('')
                                    ->content('Used by any landlord who hasn\'t set their own SMS gateway in their Settings.'),

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
                                                    $phone = trim((string) ($livewire->data['test_sms_phone'] ?? ''));

                                                    if ($phone === '') {
                                                        Notification::make()->danger()->title('Enter a phone number first')->send();
                                                        return;
                                                    }

                                                    try {
                                                        SmsHelper::sendWithConfig($phone, 'This is a test message from ' . ($livewire->data['app_name'] ?? config('app.name')) . ' - your platform SMS settings are working.', [
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

                        Forms\Components\Tabs\Tab::make('Email')
                            ->schema([
                                Forms\Components\Placeholder::make('smtp_fallback_note')
                                    ->label('')
                                    ->content('Used by any landlord who hasn\'t set their own SMTP in their own Settings > Email tab. A Gmail account works well here (use an App Password, not the account password - Google Account > Security > 2-Step Verification > App Passwords).'),

                                Forms\Components\TextInput::make('smtp.host')
                                    ->label('SMTP Host')
                                    ->placeholder('smtp.gmail.com')
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
                                    ->placeholder('you@gmail.com')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('smtp.password')
                                    ->label('SMTP Password')
                                    ->password()
                                    ->helperText('For Gmail, this must be a 16-character App Password, not your normal Google password.')
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

                        Forms\Components\Tabs\Tab::make('Subscription Billing')
                            ->schema([
                                Forms\Components\Placeholder::make('subscription_billing_note')
                                    ->label('')
                                    ->content('These credentials collect payment FROM landlords FOR their own Renty subscription - completely separate from a landlord\'s own M-Pesa/Pesapal, which each business sets individually in their own Settings > Payments tab to collect rent from their tenants.'),

                                Forms\Components\Section::make('Pesapal')
                                    ->schema([
                                        Forms\Components\TextInput::make('subscription_pesapal.consumer_key')
                                            ->label('Pesapal Consumer Key')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('subscription_pesapal.consumer_secret')
                                            ->label('Pesapal Consumer Secret')
                                            ->password()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('subscription_pesapal.webhook_secret')
                                            ->label('Pesapal Webhook Secret')
                                            ->password()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('subscription_pesapal.ipn_id')
                                            ->label('Pesapal IPN ID')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('subscription_pesapal.callback_url')
                                            ->label('Pesapal Callback URL')
                                            ->url()
                                            ->maxLength(1024),

                                        Forms\Components\Toggle::make('subscription_pesapal.sandbox')
                                            ->label('Use Pesapal Sandbox')
                                            ->default(true),

                                        Forms\Components\TextInput::make('subscription_pesapal.currency')
                                            ->label('Currency')
                                            ->default('KES')
                                            ->maxLength(10),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('M-Pesa (Daraja API)')
                                    ->schema([
                                        Forms\Components\TextInput::make('subscription_mpesa.consumer_key')
                                            ->label('Daraja API Key')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('subscription_mpesa.consumer_secret')
                                            ->label('Daraja API Secret')
                                            ->password()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('subscription_mpesa.business_shortcode')
                                            ->label('Business Short Code')
                                            ->maxLength(10),

                                        Forms\Components\TextInput::make('subscription_mpesa.passkey')
                                            ->label('M-Pesa Online Passkey')
                                            ->password()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('subscription_mpesa.callback_url')
                                            ->label('M-Pesa Callback URL')
                                            ->url()
                                            ->maxLength(1024),

                                        Forms\Components\Toggle::make('subscription_mpesa.sandbox')
                                            ->label('Use Sandbox (Daraja Test)')
                                            ->default(true),

                                        Forms\Components\TextInput::make('subscription_mpesa.currency')
                                            ->label('Currency')
                                            ->default('KES')
                                            ->maxLength(10),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $settings = Setting::forLandlord(null);
        $settings->payload = $this->form->getState();
        $settings->save();

        Notification::make()
            ->title('Platform settings saved')
            ->success()
            ->send();
    }
}
