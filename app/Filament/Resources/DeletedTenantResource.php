<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeletedTenantResource\Pages;
use App\Models\DeletedTenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Location;

class DeletedTenantResource extends Resource
{
    protected static ?string $model = DeletedTenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Vacated Tenants';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $title = 'Vacated Tenants';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tenant Information')
                    ->schema([
                        Forms\Components\TextInput::make('tenant_name')
                            ->label('Tenant Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('id_number')
                            ->label('ID Number')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Financial Summary')
                    ->schema([
                        Forms\Components\TextInput::make('total_invoiced')
                            ->label('Total Invoiced')
                            ->prefix('KES')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_paid')
                            ->label('Total Paid')
                            ->prefix('KES')
                            ->disabled(),
                        Forms\Components\TextInput::make('outstanding_balance')
                            ->label('Outstanding Balance')
                            ->prefix('KES')
                            ->disabled(),
                        Forms\Components\TextInput::make('overpayment')
                            ->label('Overpayment')
                            ->prefix('KES')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Invoice Statistics')
                    ->schema([
                        Forms\Components\TextInput::make('invoices_count')
                            ->label('Total Invoices')
                            ->disabled(),
                        Forms\Components\TextInput::make('paid_invoices_count')
                            ->label('Paid Invoices')
                            ->disabled(),
                        Forms\Components\TextInput::make('unpaid_invoices_count')
                            ->label('Unpaid Invoices')
                            ->disabled(),
                        Forms\Components\TextInput::make('partial_invoices_count')
                            ->label('Partial Invoices')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Previous House')
                    ->schema([
                        Forms\Components\TextInput::make('previous_house')
                            ->label('House Name')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Archival Dates')
                    ->schema([
                        Forms\Components\DateTimeInput::make('deleted_at')
                            ->label('Deleted On')
                            ->disabled(),
                        Forms\Components\DateTimeInput::make('auto_delete_at')
                            ->label('Auto-Delete On')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant_name')
                    ->label('Tenant Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location_name')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('previous_house')
                    ->label('Previous House')
                    ->sortable(),
                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->label('Outstanding')
                    ->money('KES', 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('overpayment')
                    ->label('Overpayment')
                    ->money('KES', 0)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoices_count')
                    ->label('Invoices')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unpaid_invoices_count')
                    ->label('Due')
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('auto_delete_at')
                    ->label('Auto-Delete')
                    ->dateTime('d M Y')
                    ->color(fn ($state) => $state->diffInDays(now()) <= 7 ? 'danger' : 'warning')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name')
                    ->searchable()
                    ->preload(),

                Filter::make('has_outstanding')
                    ->label('Has Outstanding Balance')
                    ->query(fn (Builder $query) => $query->where('outstanding_balance', '>', 0)),

                Filter::make('has_overpayment')
                    ->label('Has Overpayment')
                    ->query(fn (Builder $query) => $query->where('overpayment', '>', 0)),

                SelectFilter::make('unpaid_invoices_count')
                    ->label('Invoice Due Status')
                    ->options([
                        '0' => 'No Outstanding',
                        '1' => 'Has Outstanding',
                    ])
                    ->query(fn (Builder $query, $state) => match ($state) {
                        '1' => $query->where('unpaid_invoices_count', '>', 0)->orWhere('partial_invoices_count', '>', 0),
                        '0' => $query->where('unpaid_invoices_count', 0)->where('partial_invoices_count', 0),
                        default => $query,
                    }),

                Filter::make('deleted_at')
                    ->label('Deleted In Last 30 Days')
                    ->query(fn (Builder $query) => $query->where('deleted_at', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk actions can be added here
                ]),
            ])
            ->defaultSort('deleted_at', 'desc');
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
            'index' => Pages\ListDeletedTenants::route('/'),
            'view' => Pages\ViewDeletedTenant::route('/{record}'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Caretakers can only see deleted tenants from their assigned location
        if ($user && $user->role === 'caretaker' && $user->location_id) {
            // Note: The house_id is stored in house_name or we need location_id in DeletedTenant
            // For now, we filter by location_id if it exists in the model
            $query->where('location_id', $user->location_id);
        }

        return $query;
    }
}
