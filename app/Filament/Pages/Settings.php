<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasLandlordSettingsSchema;
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
    use HasLandlordSettingsSchema;

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
     * Role-based access: Only admin/landlord can access Settings.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['admin', 'landlord'])) {
            abort(403, 'Unauthorized');
        }

        $settings = Setting::forLandlord($user->landlord_id);
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
                    ->tabs(static::landlordSettingsTabs()),
            ]);
    }

    /**
     * Persist settings
     */
    public function save(): void
    {
        $settings = Setting::forLandlord(auth()->user()->landlord_id);
        $payload = $settings->payload ?? [];
        $incoming = $this->form->getState();

        // Hidden fields aren't proof against a tampered request - a 'landlord' can't
        // touch admin-only settings server-side either, matching what
        // HasLandlordSettingsSchema hides from them (see its ->visible() calls).
        $isAdminRole = in_array(auth()->user()->role, ['admin', 'superadmin'], true);
        if (!$isAdminRole) {
            foreach (['app_name', 'sms_url', 'sms_api_key', 'sms_partner_id', 'sms_sender_id', 'smtp', 'mpesa', 'pesapal'] as $lockedKey) {
                unset($incoming[$lockedKey]);
            }
            if (!$settings->hasPaymentGatewayCredentials()) {
                unset($incoming['payment_mode']);
            }
        }

        $payload = array_replace_recursive($payload, $incoming);

        if ($isAdminRole && ($incoming['payment_mode'] ?? null) === 'automatic') {
            $payload['payment_gateway_request']['status'] = 'fulfilled';
        }

        $settings->payload = $payload;
        $settings->save();

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

}
