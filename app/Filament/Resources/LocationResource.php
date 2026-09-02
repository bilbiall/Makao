<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

//for the input form for users
use App\Models\City;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

//to display columns in users
use Filament\Tables\Columns\TextColumn;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-s-map-pin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //inputs
                TextInput::make('location_name')->required(),

                Select::make('city_id')
                    ->label('City / town')
                    ->options(City::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    // Not a real Location column - purely narrows the area_id
                    // options below, stripped in CreateLocation/EditLocation
                    // before save the same way HouseResource does is_short_term.
                    ->dehydrated(false)
                    ->afterStateHydrated(fn ($component, $record) => $component->state($record?->area?->city_id)),

                Select::make('area_id')
                    ->label('Area')
                    ->options(fn (Get $get) => $get('city_id')
                        ? \App\Models\Area::where('city_id', $get('city_id'))->orderBy('name')->pluck('name', 'id')
                        : [])
                    ->searchable()
                    ->live()
                    ->helperText('Pick a city above first. Setting this also fills in "Area (free text)" below automatically.')
                    ->afterStateUpdated(fn ($state, callable $set) => $set('geo_id', \App\Models\Area::find($state)?->name)),

                TextInput::make('geo_id')
                    ->label('Area (free text)')
                    ->helperText('Used as-is if the area above isn\'t in our list yet - e.g. a city/town not seeded yet.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //column heads
                TextColumn::make('location_name'),
                TextColumn::make('area.city.name')->label('City'),
                TextColumn::make('geo_id')->label('Area'),
                //TextColumn::make('timestamp'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }

    /**
     * Only admin and landlord can view locations. Caretakers can't access locations.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->landlord_id) {
            return true;
        }

        return app(\App\Services\PackageLimitService::class)
            ->canAdd('locations', \App\Models\Landlord::find($user->landlord_id));
    }
}
