<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "The locations we're open to" - every city/town in the reference list
 * (KenyaLocationsSeeder + KenyaCountyTownsSeeder), with a per-city toggle for
 * whether landlords can currently pick it when creating a property. A city
 * left closed just doesn't appear in any location picker - see
 * City::breakdown().
 */
class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Locations';

    protected static ?string $modelLabel = 'city';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Toggle::make('is_open')
                    ->label('Open')
                    ->helperText('Landlords can only pick this city when adding a property while it\'s open.')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('areas_count')->counts('areas')->label('Areas'),
                Tables\Columns\ToggleColumn::make('is_open')->label('Open'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_open')->label('Open'),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('open')
                        ->label('Mark open')
                        ->icon('heroicon-o-lock-open')
                        ->action(fn ($records) => $records->each->update(['is_open' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('close')
                        ->label('Mark closed')
                        ->icon('heroicon-o-lock-closed')
                        ->color('gray')
                        ->action(fn ($records) => $records->each->update(['is_open' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
        ];
    }
}
