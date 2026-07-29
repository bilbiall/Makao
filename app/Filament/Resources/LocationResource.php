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
use Filament\Forms\Components\TextInput;

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
                TextInput::make('geo_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //column heads
                TextColumn::make('location_name'),
                TextColumn::make('geo_id')->label('City'),
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
