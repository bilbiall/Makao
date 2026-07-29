<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('landlord_id')
                    ->relationship('landlord', 'name')
                    ->required()
                    ->disabledOn('edit'),

                Forms\Components\Select::make('package_id')
                    ->relationship('package', 'name')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'trialing' => 'Trialing',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),

                Forms\Components\DateTimePicker::make('starts_at')->required(),
                Forms\Components\DateTimePicker::make('trial_ends_at'),
                Forms\Components\DateTimePicker::make('expires_at')->label('Renewal / Expiry Date'),

                Forms\Components\Section::make('Manually Recorded Payment')
                    ->schema([
                        Forms\Components\TextInput::make('payment_reference'),
                        Forms\Components\TextInput::make('amount_paid')->numeric()->prefix('KES'),
                        Forms\Components\DateTimePicker::make('last_payment_at'),
                        Forms\Components\Textarea::make('payment_notes'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('landlord.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('package.name')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'expired', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('expires_at')->label('Renews/Expires')->date()->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')->money('KES'),
                Tables\Columns\TextColumn::make('last_payment_at')->label('Last Paid')->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trialing' => 'Trialing',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring in 7 days')
                    ->query(fn ($query) => $query->whereBetween('expires_at', [now(), now()->addDays(7)])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('expires_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
