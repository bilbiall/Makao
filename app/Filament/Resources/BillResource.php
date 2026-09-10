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

                Forms\Components\Repeater::make('items')
                    ->label('Charges')
                    ->relationship('items')
                    ->schema([
                        Select::make('bill_type_id')
                            ->label('Type')
                            ->options(fn () => \App\Models\BillType::where('landlord_id', auth()->user()->landlord_id)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $type = $state ? \App\Models\BillType::find($state) : null;

                                if ($type && $type->default_amount !== null) {
                                    $set('amount', $type->default_amount);
                                }
                            }),

                        TextInput::make('amount')
                            ->label('Amount (KES)')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->columns(2)
                    ->addActionLabel('+ Add a charge')
                    ->defaultItems(0)
                    ->helperText('No bill types set up yet? Add one first under Bill Types.'),

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

                TextColumn::make('charges')
                    ->label('Charges')
                    ->getStateUsing(fn ($record) => $record->items
                        ->map(fn ($item) => ($item->billType?->name ?? 'Deleted type') . ': ' . number_format($item->amount))
                        ->implode(', ') ?: '—')
                    ->wrap(),

                TextColumn::make('total')
                    ->label('Total (KES)')
                    ->money('KES')
                    ->getStateUsing(fn ($record) => $record->total),

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
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->hasPermission(\App\Support\StaffPermissions::EDIT_BILLS)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasPermission(\App\Support\StaffPermissions::DELETE_BILLS)),
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

    public static function canCreate(): bool
    {
        return auth()->user()->hasPermission(\App\Support\StaffPermissions::CREATE_BILLS);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasPermission(\App\Support\StaffPermissions::EDIT_BILLS);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->hasPermission(\App\Support\StaffPermissions::DELETE_BILLS);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasPermission(\App\Support\StaffPermissions::DELETE_BILLS);
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Manager/Caretaker are narrowed to their assigned properties, Agent is denied
        // entirely - see StaffScope::onTenantChild() (this used to duplicate that logic
        // inline without the Agent deny-path, silently exposing every tenant's bills to
        // any Agent account with Filament access).
        return \App\Support\StaffScope::onTenantChild($query);
    }
}
