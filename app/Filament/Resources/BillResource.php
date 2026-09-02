<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\Pages;
use App\Filament\Resources\BillResource\RelationManagers;
use App\Models\Bill;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;


use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\Filter;

use Illuminate\Support\Carbon;




class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-s-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'tenant_name')
                    ->searchable()
                    ->required(),

                DatePicker::make('bill_month')
                    ->label('Bill Month')
                    ->required(),

                TextInput::make('electricity')->numeric()->default(0)->required(),
                TextInput::make('water')->numeric()->default(0)->required(),
                TextInput::make('trash')->numeric()->default(0)->required(),
                TextInput::make('internet')->numeric()->default(0)->required(),

                Textarea::make('note')->nullable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //display
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

                TextColumn::make('note')
                    ->label('Note')
                    ->limit(40),
            ])
            ->filters([
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

                SelectFilter::make('location')
                    ->label('Location')
                    ->relationship('tenant.house.location', 'location_name')
                    ->searchable()
                    ->preload(),


                // ✅ Filter bills by both month and year
                /*Filter::make('bill_month')
                    ->label('Filter by Month & Year')
                    ->form([
                        // The user picks any date — we'll extract the month and year from it
                        DatePicker::make('month')->label('Pick Month'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['month'])) {
                            $query->whereMonth('bill_month', $data['month']->format('m'))
                                ->whereYear('bill_month', $data['month']->format('Y'));
                        }
                    }),*/
                /*Filter::make('bill_month')
                    ->label('Filter by Month')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('month')->label('Month'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['month']) {
                            $query->whereMonth('bill_month', $data['month']->month)
                                ->whereYear('bill_month', $data['month']->year);
                        }
                    }),*/
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
            'create' => Pages\CreateBill::route('/create'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Manager/Caretaker are narrowed to their assigned properties (staff_assignments pivot).
        // Note: Bill has no direct house() relation, only tenant() - the previous version of
        // this check called whereHas('house', ...) which doesn't exist on Bill and would have
        // thrown for any caretaker/manager viewing this resource. Fixed to go via tenant.house.
        if (\App\Support\StaffScope::isScopedStaff()) {
            $locationIds = \App\Support\StaffScope::locationIds();
            $query->whereHas('tenant.house', function ($q) use ($locationIds) {
                $q->whereIn('location_id', $locationIds);
            });
        }

        return $query;
    }
}
