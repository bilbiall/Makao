<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MpesaChannelResource\Pages;
use App\Models\MpesaChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

/**
 * Lets a landlord give any of their properties its own M-Pesa shortcode/credentials
 * for STK push, and (once the superadmin has enabled it for them) register that
 * same shortcode for C2B Paybill reconciliation. A channel with no property picked
 * is the landlord's default, used by any property without one of its own - see
 * MpesaChannel::resolveFor() and MpesaService/BnbMpesaService's loadConfigForLocation().
 */
class MpesaChannelResource extends Resource
{
    protected static ?string $model = MpesaChannel::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'M-Pesa Channels';
    protected static ?string $navigationGroup = 'Payments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('setup_guide_link')
                    ->hiddenLabel()
                    ->content(new \Illuminate\Support\HtmlString(
                        '<div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">'
                        . 'New here? Read the full <a href="' . route('app.admin.mpesa-guide') . '" class="text-emerald-700 dark:text-emerald-400 underline font-semibold">M-Pesa Setup Guide</a> - where to get your Consumer Key/Secret/Passkey, what to enter below, and how to test STK push and C2B step by step.'
                        . '</div>'
                    )),

                Forms\Components\Placeholder::make('local_url_warning')
                    ->hiddenLabel()
                    ->visible(fn () => static::appUrlIsLocal())
                    ->content(new \Illuminate\Support\HtmlString(
                        '<div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">'
                        . '<strong>Your site is on ' . e(config('app.url')) . '</strong> - Safaricom cannot reach this to deliver an STK result or a C2B payment, so nothing will come back even with correct keys. '
                        . 'For testing: run <code>ngrok http 80</code> (or your XAMPP port), then temporarily set <code>APP_URL</code> in your <code>.env</code> to the <code>https://...ngrok-free.app</code> URL it gives you and run <code>php artisan config:clear</code> before you test Pay Now or Register C2B. '
                        . 'See the "Local testing" section of the <a href="' . route('app.admin.mpesa-guide') . '" class="underline font-semibold">M-Pesa Setup Guide</a> for the exact steps.'
                        . '</div>'
                    )),

                Forms\Components\Section::make('Channel')
                    ->description('Which of your properties this Paybill or Till belongs to.')
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label('Label')
                            ->placeholder('e.g. Kilimani Apartments Paybill')
                            ->maxLength(255),

                        Forms\Components\Select::make('location_id')
                            ->label('Applies to')
                            ->relationship('location', 'location_name')
                            ->placeholder('All my properties (default channel)')
                            ->helperText('Leave blank to make this the default used by any property without its own channel.')
                            ->searchable(),

                        Forms\Components\TextInput::make('business_shortcode')
                            ->label('Paybill / Till Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Daraja app credentials')
                    ->description('The Consumer Key and Secret from your Daraja app - Safaricom uses this same pair to authenticate BOTH STK push and C2B calls, there is no separate key per feature. The STK Passkey and C2B registration below are configured separately because those two use different parts of the Daraja API, even though they share this same key/secret.')
                    ->schema([
                        Forms\Components\TextInput::make('consumer_key')
                            ->label('Daraja Consumer Key')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('consumer_secret')
                            ->label('Daraja Consumer Secret')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('sandbox')
                            ->label('Use Sandbox (Daraja Test)')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('STK push ("Pay Now" button)')
                    ->description('Lets a tenant pay by tapping "Pay Now" and confirming a prompt on their phone - only these two fields are specific to STK, on top of the shared Daraja credentials above.')
                    ->schema([
                        Forms\Components\Toggle::make('stk_enabled')
                            ->label('Use for "Pay Now" (STK push)')
                            ->default(true),

                        Forms\Components\TextInput::make('passkey')
                            ->label('M-Pesa Online Passkey')
                            ->password()
                            ->revealable()
                            ->helperText('STK-only - not used anywhere in the C2B flow below. Needed in live mode, optional in sandbox.')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('C2B (Paybill payment reconciliation)')
                    ->description('Lets a tenant who pays this Paybill/Till directly (outside "Pay Now") still get matched to their invoice automatically - uses the same Daraja credentials and the Paybill/Till number above, no separate key.')
                    ->schema([
                        Forms\Components\Placeholder::make('c2b_status')
                            ->label('Status')
                            ->content(fn (?MpesaChannel $record) => match (true) {
                                !auth()->user()?->landlord?->c2b_enabled => 'Not enabled for your account yet - contact support to have this turned on.',
                                $record && $record->c2b_registered_at => 'Registered with Safaricom on ' . $record->c2b_registered_at->format('d M Y, H:i') . '. Use the "Re-register C2B" button above if you change these credentials.',
                                $record => 'Not yet registered - save this channel, then use the "Register C2B" button above.',
                                default => 'Save this channel first, then register it for C2B.',
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Label')
                    ->placeholder('(unlabeled)')
                    ->searchable(),
                TextColumn::make('location.location_name')
                    ->label('Property')
                    ->placeholder('All properties (default)'),
                TextColumn::make('business_shortcode')
                    ->label('Shortcode')
                    ->copyable(),
                IconColumn::make('stk_enabled')
                    ->label('STK')
                    ->boolean(),
                IconColumn::make('c2b_enabled')
                    ->label('C2B')
                    ->boolean(),
                TextColumn::make('c2b_registered_at')
                    ->label('C2B Registered')
                    ->dateTime()
                    ->placeholder('Not registered'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMpesaChannels::route('/'),
            'create' => Pages\CreateMpesaChannel::route('/create'),
            'edit' => Pages\EditMpesaChannel::route('/{record}/edit'),
        ];
    }

    /** Only admin/landlord manage payment credentials - not caretaker/manager/agent,
     *  same restriction LocationResource already applies to property management. */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    /** Safaricom can't call back to localhost/127.0.0.1 - flags this in the form
     *  since it's the most common reason a correctly-configured channel appears
     *  to "do nothing" when testing STK/C2B from a local XAMPP install. */
    public static function appUrlIsLocal(): bool
    {
        $url = config('app.url');

        return str_contains($url, 'localhost') || str_contains($url, '127.0.0.1');
    }
}
