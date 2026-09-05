<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\MpesaChannelResource\Pages;
use App\Models\Landlord;
use App\Models\Location;
use App\Models\MpesaChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

/**
 * Superadmin's own view onto every landlord's M-Pesa Channels - the founder-facing
 * counterpart to App\Filament\Resources\MpesaChannelResource (which a landlord/admin
 * uses for their own channels only). Lets support set up or troubleshoot a specific
 * landlord's Daraja credentials (live or sandbox) and register C2B on their behalf,
 * without needing to log in as them. MpesaChannel's LandlordScope only filters queries
 * for a landlord/admin-scoped user (see App\Support\CurrentLandlord::id(), which
 * returns null for 'superadmin') - so this resource sees every landlord's channels
 * unscoped with no extra query override needed.
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
                Forms\Components\Select::make('landlord_id')
                    ->label('Landlord')
                    ->relationship('landlord', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                Forms\Components\TextInput::make('label')
                    ->label('Label')
                    ->placeholder('e.g. Kilimani Apartments Paybill')
                    ->maxLength(255),

                Forms\Components\Select::make('location_id')
                    ->label('Applies to')
                    ->options(fn (Get $get) => $get('landlord_id')
                        ? Location::withoutGlobalScopes()->where('landlord_id', $get('landlord_id'))->pluck('location_name', 'id')
                        : [])
                    ->placeholder('All properties for this landlord (default channel)')
                    ->helperText('Leave blank to make this the default used by any property without its own channel. Pick a landlord first.')
                    ->searchable(),

                Forms\Components\TextInput::make('business_shortcode')
                    ->label('Paybill / Till Number')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),

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

                Forms\Components\TextInput::make('passkey')
                    ->label('M-Pesa Online Passkey')
                    ->password()
                    ->revealable()
                    ->helperText('Needed for STK push in live mode - optional in sandbox.')
                    ->maxLength(255),

                Forms\Components\Toggle::make('sandbox')
                    ->label('Use Sandbox (Daraja Test)')
                    ->helperText('Test against Safaricom\'s sandbox environment first before flipping to live credentials.')
                    ->default(true),

                Forms\Components\Toggle::make('stk_enabled')
                    ->label('Use for "Pay Now" (STK push)')
                    ->default(true),

                Forms\Components\Placeholder::make('c2b_status')
                    ->label('C2B (Paybill payment) reconciliation')
                    ->content(function (Get $get, ?MpesaChannel $record) {
                        $landlord = $get('landlord_id') ? Landlord::find($get('landlord_id')) : $record?->landlord;

                        return match (true) {
                            !$landlord?->c2b_enabled => 'Not enabled for this landlord yet - toggle "C2B (Paybill) reconciliation enabled" on the Landlord\'s edit page first.',
                            $record && $record->c2b_registered_at => 'Registered with Safaricom on ' . $record->c2b_registered_at->format('d M Y, H:i') . '. Use the "Re-register C2B" button above if you change these credentials.',
                            $record => 'Not yet registered - save this channel, then use the "Register C2B" button above.',
                            default => 'Save this channel first, then register it for C2B.',
                        };
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('landlord.name')
                    ->label('Landlord')
                    ->searchable()
                    ->sortable(),
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
                IconColumn::make('sandbox')
                    ->label('Sandbox')
                    ->boolean(),
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
            ->filters([
                Tables\Filters\SelectFilter::make('landlord')
                    ->relationship('landlord', 'name')
                    ->searchable()
                    ->preload(),
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
}
