<?php

namespace App\Filament\Superadmin\Resources\LandlordResource\Pages;

use App\Filament\Concerns\HasLandlordSettingsSchema;
use App\Filament\Superadmin\Resources\LandlordResource;
use App\Models\Landlord;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

/**
 * Lets the superadmin view/edit a specific landlord's own settings (SMS/email
 * templates, SMTP, M-Pesa/Pesapal credentials, payment mode) for support purposes -
 * the landlord keeps their own Settings page unchanged; this is a read/write mirror
 * onto the same underlying Setting row, scoped to whichever landlord is being viewed
 * rather than the currently authenticated user.
 */
class ManageLandlordSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use HasLandlordSettingsSchema;

    protected static string $resource = LandlordResource::class;

    protected static string $view = 'filament.superadmin.resources.landlord-resource.pages.manage-landlord-settings';

    public Landlord $record;

    public ?array $data = [];

    public function mount(Landlord $record): void
    {
        // Filament already resolves {record} into a Landlord via route-model-binding
        // (same as EditRecord/ViewRecord) before this runs - re-querying it here (e.g.
        // via findOrFail($record)) would pass an already-hydrated model where a scalar
        // ID is expected, silently fail, and 404 (ModelNotFoundException renders as a
        // 404 and isn't logged by Laravel's default handler, which made this tricky to
        // spot - the route matched fine and mount() was reached, it just failed inside).
        $this->record = $record;

        $settings = Setting::forLandlord($this->record->id);
        $payload = $settings->payload ?? [];

        if (empty($payload['app_name'])) {
            $payload['app_name'] = $this->record->name;
        }

        $this->form->fill($payload);
    }

    public function getTitle(): string
    {
        return "Settings - {$this->record->name}";
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs(static::landlordSettingsTabs()),
            ]);
    }

    public function save(): void
    {
        $settings = Setting::forLandlord($this->record->id);
        $settings->payload = $this->form->getState();
        $settings->save();

        Notification::make()
            ->title("Settings saved for {$this->record->name}")
            ->success()
            ->send();
    }
}
