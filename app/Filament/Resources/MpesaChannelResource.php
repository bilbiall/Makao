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
                    ->default(true),

                Forms\Components\Toggle::make('stk_enabled')
                    ->label('Use for "Pay Now" (STK push)')
                    ->default(true),

                Forms\Components\Placeholder::make('c2b_status')
                    ->label('C2B (Paybill payment) reconciliation')
                    ->content(fn (?MpesaChannel $record) => match (true) {
                        !auth()->user()?->landlord?->c2b_enabled => 'Not enabled for your account yet - contact support to have this turned on.',
                        $record && $record->c2b_registered_at => 'Registered with Safaricom on ' . $record->c2b_registered_at->format('d M Y, H:i') . '. Use the "Re-register C2B" button above if you change these credentials.',
                        $record => 'Not yet registered - save this channel, then use the "Register C2B" button above.',
                        default => 'Save this channel first, then register it for C2B.',
                    }),
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
}
