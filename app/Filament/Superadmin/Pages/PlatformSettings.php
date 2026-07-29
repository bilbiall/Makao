<?php

namespace App\Filament\Superadmin\Pages;

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
        $this->form->fill($settings->payload ?? []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Analytics')
                    ->description('Applies to the public marketing site (renty.co.ke home/pricing/etc.), not the authenticated landlord/tenant panels.')
                    ->schema([
                        Forms\Components\TextInput::make('google_analytics_id')
                            ->label('Google Analytics Measurement ID')
                            ->helperText('From Google Analytics (GA4): Admin > Data Streams > your stream. Looks like G-XXXXXXXXXX.')
                            ->placeholder('G-XXXXXXXXXX')
                            ->maxLength(50),
                    ]),

                Forms\Components\Section::make('Support')
                    ->schema([
                        Forms\Components\TextInput::make('platform_support_email')
                            ->label('Platform Support Email')
                            ->email()
                            ->helperText('Shown on the marketing site for general enquiries (not a specific landlord\'s support contact).')
                            ->maxLength(255),
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
