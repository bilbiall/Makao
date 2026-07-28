<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HouseResource\Pages;
use App\Filament\Resources\HouseResource\RelationManagers;
use App\Models\House;
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



class HouseResource extends Resource
{
    protected static ?string $model = House::class;

    protected static ?string $navigationIcon = 'heroicon-s-home';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //form for new house
                TextInput::make('house_name')->required(),
                //TextInput::make('number_of_rooms')->numeric()->required(),
                //TextInput::make('num_of_bedrooms')->required(),
                TextInput::make('house_type')
                    ->label('House Type')
                    ->placeholder('e.g. Bedsitter, 1 Bedroom, 2 Bedroom')
                    ->required(),

                TextInput::make('rent_amount')->numeric()->required(),

                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name')
                    ->searchable()
                    ->required(),

                Select::make('house_status')
                    ->options([
                        'Vacant' => 'Vacant',
                        'Occupied' => 'Occupied',
                    ])
                    ->default('Vacant')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //to display a list of the houses
                TextColumn::make('house_name')->searchable(),
                //TextColumn::make('number_of_rooms'),
                TextColumn::make('house_type'),
                TextColumn::make('rent_amount')->money('KES'),
                TextColumn::make('location.location_name')->label('Location'),
                TextColumn::make('house_status'),
                TextColumn::make('created_at')->dateTime()
            ])
            ->filters([
                //filter by location
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name')
                    ->searchable(),

                SelectFilter::make('house_status')
                    ->label('Status')
                    ->options([
                        'Vacant' => 'Vacant',
                        'Occupied' => 'Occupied',
                    ]),
                /*SelectFilter::make('location_id')
                    ->label('Filter by Location')
                    ->relationship('location', 'location_name')
                    ->searchable()*/
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
            'index' => Pages\ListHouses::route('/'),
            'create' => Pages\CreateHouse::route('/create'),
            'edit' => Pages\EditHouse::route('/{record}/edit'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Caretakers can only see houses in their assigned location
        if ($user && $user->role === 'caretaker' && $user->location_id) {
            $query->where('location_id', $user->location_id);
        }

        return $query;
    }
}
