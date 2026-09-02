<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IssueResource\Pages;
use App\Filament\Resources\IssueResource\RelationManagers;
use App\Models\Issue;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Only status is editable
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                    ])
                    ->native(false)
                    ->label('Update Status'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //views
                Tables\Columns\TextColumn::make('tenant.tenant_name')->label('Tenant'),
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Location')
                    ->options(Location::pluck('location_name', 'id')->toArray())
                    ->query(fn (Builder $query, $value = null) => $query->when($value !== null, fn () => $query->whereRelation('tenant.house', 'location_id', $value))),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                    ])
                    ->query(fn (Builder $query, $value = null) => $query->when($value !== null, fn () => $query->where('status', $value))),
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

    // Disable creation from the admin panel (tenants still use their own resource)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssues::route('/'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
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
        if (\App\Support\StaffScope::isScopedStaff()) {
            $locationIds = \App\Support\StaffScope::locationIds();
            $query->whereHas('tenant.house', function ($q) use ($locationIds) {
                $q->whereIn('location_id', $locationIds);
            });
        }

        return $query;
    }
}
