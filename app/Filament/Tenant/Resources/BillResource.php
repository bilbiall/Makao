<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\BillResource\Pages;
use App\Filament\Tenant\Resources\BillResource\RelationManagers;
use App\Models\Bill;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;


use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\Filter;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //tenants bills to display
                TextColumn::make('tenant.tenant_name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bill_month')
                    ->label('Bill Month')
                    ->date('F Y')
                    ->sortable(),

                TextColumn::make('water')
                    ->label('Water (KES)')
                    ->money('KES'),

                TextColumn::make('electricity')
                    ->label('Electricity (KES)')
                    ->money('KES'),

                TextColumn::make('internet')
                    ->label('Internet (KES)')
                    ->money('KES'),

                TextColumn::make('trash')
                    ->label('Trash (KES)')
                    ->money('KES'),

                TextColumn::make('total')
                    ->label('Total (KES)')
                    ->money('KES')
                    ->getStateUsing(
                        fn($record) =>
                        $record->water + $record->electricity + $record->internet + $record->trash
                    ),
            ])
            ->filters([
                //tenants filters
                Filter::make('bill_month')
                    ->label('Filter by Month & Year')
                    ->form([
                        // User picks any date — we extract the month and year
                        DatePicker::make('month')->label('Pick any date of the Month'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $selectedDate = $data['month'] ?? now();

                        // Ensure it's a Carbon instance if user selects a date
                        if (is_string($selectedDate)) {
                            $selectedDate = \Carbon\Carbon::parse($selectedDate);
                        }

                        $query->whereMonth('bill_month', $selectedDate->format('m'))
                            ->whereYear('bill_month', $selectedDate->format('Y'));
                    }),

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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBills::route('/'),
            //'create' => Pages\CreateBill::route('/create'),
            //'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }

    // Disable creation from the tenant panel (tenants still use their own resource)
    public static function canCreate(): bool
    {
        return false;
    }

    //limit tenant to view own bills
    public static function getEloquentQuery(): Builder
    {
        $tenant = Auth::user()?->tenant;

        if (!$tenant) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('tenant_id', $tenant->id);
    }
}
