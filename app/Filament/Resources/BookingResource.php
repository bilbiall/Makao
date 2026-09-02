<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * BnB booking management - v1 table view per BNB_MODE_DESIGN.md. A calendar/timeline
 * view is a nicer UX but adds a dependency; deferred until this is validated with
 * real usage. Scoped so Manager/Caretaker see every booking in their assigned
 * properties, and Agent sees only their directly-assigned house(s).
 */
class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Bookings';
    protected static ?string $navigationGroup = 'Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('house.house_name')->label('House')->disabled(),
                Forms\Components\TextInput::make('guest_name')->label('Guest')->disabled(),
                Forms\Components\Textarea::make('notes')->label('Notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('house.house_name')->label('House')->searchable(),
                Tables\Columns\TextColumn::make('guest_name')->label('Guest')->searchable(),
                Tables\Columns\TextColumn::make('guest_phone')->label('Phone')->toggleable(),
                Tables\Columns\TextColumn::make('check_in')->date()->sortable(),
                Tables\Columns\TextColumn::make('check_out')->date()->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->money('KES')->label('Total'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'confirmed', 'checked_in' => 'success',
                        'checked_out' => 'gray',
                        'cancelled' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'deposit_paid' => 'warning',
                        'refunded' => 'gray',
                        default => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('house_id')
                    ->label('House')
                    ->relationship('house', 'house_name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'checked_in' => 'Checked in',
                        'checked_out' => 'Checked out',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->color('success')
                    ->icon('heroicon-s-check')
                    ->requiresConfirmation()
                    ->action(fn (Booking $record) => $record->update(['status' => 'confirmed', 'expires_at' => null]))
                    ->visible(fn (Booking $record) => $record->status === 'pending'),

                Tables\Actions\Action::make('check_in')
                    ->label('Check in')
                    ->color('info')
                    ->icon('heroicon-s-arrow-right-circle')
                    ->action(fn (Booking $record) => $record->update(['status' => 'checked_in']))
                    ->visible(fn (Booking $record) => $record->status === 'confirmed'),

                Tables\Actions\Action::make('check_out')
                    ->label('Check out')
                    ->color('gray')
                    ->icon('heroicon-s-arrow-left-circle')
                    ->action(fn (Booking $record) => $record->update(['status' => 'checked_out']))
                    ->visible(fn (Booking $record) => $record->status === 'checked_in'),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->icon('heroicon-s-x-mark')
                    ->requiresConfirmation()
                    ->action(fn (Booking $record) => $record->update(['status' => 'cancelled', 'expires_at' => null]))
                    ->visible(fn (Booking $record) => !in_array($record->status, ['checked_out', 'cancelled'])),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return \App\Support\StaffScope::onHouseOrAssignedHouse($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
        ];
    }
}
