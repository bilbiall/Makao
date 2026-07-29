<?php

namespace App\Filament\Superadmin\Resources\LandlordResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Renewal / Expiry Date')
                    ->helperText('Extend this when you record a renewal payment.'),

                Forms\Components\Section::make('Manually Recorded Payment')
                    ->schema([
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference (M-Pesa/bank ref)'),
                        Forms\Components\TextInput::make('amount_paid')
                            ->numeric()
                            ->prefix('KES'),
                        Forms\Components\DateTimePicker::make('last_payment_at'),
                        Forms\Components\Textarea::make('payment_notes'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('package.name'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('starts_at')->date(),
                Tables\Columns\TextColumn::make('expires_at')->date(),
                Tables\Columns\TextColumn::make('amount_paid')->money('KES'),
                Tables\Columns\TextColumn::make('last_payment_at')->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['recorded_by'] = auth()->id();
                        $data['starts_at'] ??= now();

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['recorded_by'] = auth()->id();

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
