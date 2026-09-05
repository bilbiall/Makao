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
                    ->tabs([
                        ...static::landlordSettingsTabs($this->record->id),
                        $this->mpesaChannelsTab(),
                    ]),
            ]);
    }

    /**
     * Points at the superadmin's own M-Pesa Channels resource (per-property Daraja
     * credentials + sandbox toggle + C2B registration) rather than duplicating that
     * schema here - MpesaChannel is a separate table from this page's Setting-payload
     * form, so it can't just be another field on this same form. See C2BFORRENT.md.
     */
    protected function mpesaChannelsTab(): Forms\Components\Tabs\Tab
    {
        $channels = \App\Models\MpesaChannel::withoutGlobalScopes()
            ->where('landlord_id', $this->record->id)
            ->orderByRaw('location_id is not null') // default (null) channel first
            ->get();

        $rows = $channels->map(function (\App\Models\MpesaChannel $channel) {
            $editUrl = \App\Filament\Superadmin\Resources\MpesaChannelResource::getUrl('edit', ['record' => $channel]);
            $applies = $channel->location?->location_name ?? 'All properties (default)';
            $c2b = $channel->c2b_registered_at
                ? 'Registered ' . $channel->c2b_registered_at->format('d M Y')
                : ($channel->c2b_enabled ? 'Not registered' : 'C2B off');

            return "<tr class=\"border-b border-gray-100 dark:border-gray-800\">"
                . "<td class=\"py-2 pr-3\">" . e($channel->label ?: '(unlabeled)') . "</td>"
                . "<td class=\"py-2 pr-3\">" . e($applies) . "</td>"
                . "<td class=\"py-2 pr-3 font-mono text-xs\">" . e($channel->business_shortcode) . "</td>"
                . "<td class=\"py-2 pr-3\">" . ($channel->sandbox ? 'Sandbox' : 'Live') . "</td>"
                . "<td class=\"py-2 pr-3\">{$c2b}</td>"
                . "<td class=\"py-2\"><a href=\"{$editUrl}\" class=\"text-primary-600 hover:underline\">Edit</a></td>"
                . "</tr>";
        })->implode('');

        $table = $channels->isEmpty()
            ? '<p class="text-sm text-gray-500 dark:text-gray-400">No M-Pesa Channels set up for this landlord yet.</p>'
            : '<div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead><tr class="border-b border-gray-200 dark:border-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">'
                . '<th class="py-2 pr-3">Label</th><th class="py-2 pr-3">Applies to</th><th class="py-2 pr-3">Shortcode</th><th class="py-2 pr-3">Mode</th><th class="py-2 pr-3">C2B</th><th class="py-2"></th>'
                . "</tr></thead><tbody>{$rows}</tbody></table></div>";

        $createUrl = \App\Filament\Superadmin\Resources\MpesaChannelResource::getUrl('create', ['landlord_id' => $this->record->id]);

        return Forms\Components\Tabs\Tab::make('M-Pesa Channels')
            ->schema([
                Forms\Components\Placeholder::make('mpesa_channels_note')
                    ->label('')
                    ->content(new \Illuminate\Support\HtmlString(
                        '<p class="text-sm text-gray-600 dark:text-gray-400 mb-3">'
                        . 'Per-property Daraja (M-Pesa) credentials for STK push and C2B Paybill reconciliation - live or sandbox, for testing before going live. '
                        . 'See <code>C2BFORRENT.md</code> for the full setup and Daraja sandbox testing walkthrough.'
                        . '</p>'
                        . $table
                        . "<a href=\"{$createUrl}\" class=\"mt-3 inline-block rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-500\">Add M-Pesa Channel</a>"
                    )),
            ]);
    }

    public function save(): void
    {
        $settings = Setting::forLandlord($this->record->id);
        $payload = $settings->payload ?? [];
        $incoming = $this->form->getState();

        // Superadmin is the one fulfilling a landlord's automatic-payment request -
        // setting it live here is what clears their pending request banner.
        if (($incoming['payment_mode'] ?? null) === 'automatic') {
            $payload['payment_gateway_request']['status'] = 'fulfilled';
        }

        $settings->payload = array_replace_recursive($payload, $incoming);
        $settings->save();

        Notification::make()
            ->title("Settings saved for {$this->record->name}")
            ->success()
            ->send();
    }
}
