<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\LandlordResource\Pages;
use App\Filament\Superadmin\Resources\LandlordResource\RelationManagers;
use App\Models\Landlord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LandlordResource extends Resource
{
    protected static ?string $model = Landlord::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Accounts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Business Name')
                    ->required(),

                Forms\Components\TextInput::make('contact_email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('phone_number'),

                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->required()
                    ->helperText('Suspending a landlord blocks all their staff/tenant logins immediately.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('contact_email')->searchable(),
                Tables\Columns\TextColumn::make('phone_number'),
                Tables\Columns\TextColumn::make('currentSubscription.package.name')->label('Package')->badge(),
                Tables\Columns\TextColumn::make('currentSubscription.status')
                    ->label('Subscription')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'expired', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('currentSubscription.expires_at')->label('Renews/Expires')->date(),
                Tables\Columns\TextColumn::make('locations_count')->counts('locations')->label('Locations'),
                Tables\Columns\TextColumn::make('tenants_count')->counts('tenants')->label('Tenants'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')->label('Joined')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'suspended' => 'Suspended']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('settings')
                    ->label('Settings')
                    ->icon('heroicon-o-cog')
                    ->color('gray')
                    ->url(fn (Landlord $record) => Pages\ManageLandlordSettings::getUrl(['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubscriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandlords::route('/'),
            'view' => Pages\ViewLandlord::route('/{record}'),
            'edit' => Pages\EditLandlord::route('/{record}/edit'),
            'settings' => Pages\ManageLandlordSettings::route('/{record}/settings'),
        ];
    }
}
