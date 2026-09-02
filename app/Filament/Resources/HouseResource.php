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
                Select::make('house_type')
                    ->label('House Type')
                    ->options(array_combine(House::UNIT_TYPES, House::UNIT_TYPES))
                    ->searchable()
                    ->native(false)
                    ->required(),

                TextInput::make('rent_amount')
                    ->numeric()
                    ->required(fn (callable $get) => !$get('is_short_term'))
                    ->visible(fn (callable $get) => !$get('is_short_term'))
                    ->helperText('Not used for short-stay (BnB) units - set nightly/weekly/monthly prices below instead.'),

                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name')
                    ->searchable()
                    ->required(),

                Select::make('house_status')
                    ->label('Status')
                    ->options([
                        'Vacant' => 'Vacant',
                        'Occupied' => 'Occupied',
                        'Unavailable' => 'Unavailable (e.g. renovating)',
                    ])
                    ->default('Vacant')
                    ->helperText('Occupied/Unavailable units never show on the public site regardless of the "Listed on public site" switch below.')
                    ->required(),

                Forms\Components\Toggle::make('is_published')
                    ->label('Listed on public site')
                    ->default(true)
                    ->helperText('Turn off to hide this unit from search and "all units" browsing on the public site, without changing its status or an existing tenancy. Still needs a photo (and, for rentals, to be Vacant) to actually appear when on.'),

                Forms\Components\TextInput::make('size_label')
                    ->label('Size (optional)')
                    ->placeholder('e.g. 400 sq ft'),

                Forms\Components\Textarea::make('description')
                    ->label('Public listing description')
                    ->helperText('Shown on the public "Find a house" listing page when this unit is vacant.')
                    ->rows(3),

                Forms\Components\TagsInput::make('amenities')
                    ->label('Amenities')
                    ->placeholder('Type an amenity and press Enter')
                    ->helperText('e.g. Borehole water, Backup generator, Secure parking, Wi-Fi - shown on the public listing page.')
                    ->suggestions([
                        'Borehole water', 'Backup generator', 'Secure parking', 'CCTV', 'Wi-Fi',
                        'Balcony', 'Lift', 'Master ensuite', 'Gym', 'Swimming pool', 'DSQ', 'Garden',
                        'Pet friendly', 'Kitchenette', 'Air conditioning', 'Washing machine', 'Self check-in',
                    ]),

                // Not a direct House column - saved to the house_photos table via
                // CreateHouse::afterCreate()/EditHouse::afterSave(), since a house has
                // many photos, not one. A public listing needs at least one photo to
                // appear in search (see House::scopePubliclyVisible()).
                Forms\Components\FileUpload::make('new_photos')
                    ->label('Listing photos')
                    ->multiple()
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('house-photos')
                    ->reorderable()
                    ->dehydrated(false),

                Forms\Components\Section::make('Short-stay (BnB)')
                    ->description('Turning this on takes the unit off the long-term "Find a house" page and lets you set nightly/weekly/monthly booking prices instead. Booking itself is coming in a later update - this just captures the pricing now.')
                    ->schema([
                        Forms\Components\Toggle::make('is_short_term')
                            ->label('List this unit as a short-stay (BnB)')
                            ->live()
                            ->afterStateHydrated(fn ($component, $state, $record) => $component->state($record?->listing_mode === 'short_term')),
                            // Not dehydrated(false) here - CreateHouse/EditHouse read
                            // this value in mutateFormDataBeforeCreate/Save to set the
                            // real 'listing_mode' column, then strip it before save.

                        Forms\Components\Repeater::make('pricePackages')
                            ->relationship()
                            ->label('Price packages')
                            ->visible(fn (callable $get) => $get('is_short_term'))
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->placeholder('e.g. Nightly, Weekly, Monthly'),
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('KES'),
                                Forms\Components\Select::make('billing_unit')
                                    ->options(['night' => 'Per night', 'week' => 'Per week', 'month' => 'Per month'])
                                    ->default('night')
                                    ->required(),
                            ])
                            ->addActionLabel('Add another price package')
                            ->defaultItems(1)
                            ->columns(3),
                    ])
                    ->collapsible(),
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
                TextColumn::make('house_status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Vacant' => 'success',
                        'Occupied' => 'info',
                        'Unavailable' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('listing_mode')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'short_term' ? 'Short-stay (BnB)' : 'Long-term')
                    ->color(fn (string $state) => $state === 'short_term' ? 'warning' : 'gray'),
                Tables\Columns\ToggleColumn::make('is_published')
                    ->label('Listed')
                    ->afterStateUpdated(function () {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Public listing visibility updated')
                            ->send();
                    }),
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

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Listed on public site'),
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

        // Manager/Caretaker are narrowed to their assigned properties (staff_assignments pivot).
        return \App\Support\StaffScope::onHouse($query);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user || !$user->landlord_id) {
            return true;
        }

        return app(\App\Services\PackageLimitService::class)
            ->canAdd('houses', \App\Models\Landlord::find($user->landlord_id));
    }
}
