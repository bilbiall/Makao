<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
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
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Hash;


//use App\Filament\Resources\AdminResource\Pages\SendNotification;
use App\Filament\Resources\UserResource\Pages\SendNotification;




//to display columns in users
use Filament\Tables\Columns\TextColumn;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-user-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //user form
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
                //TextInput::make('password')->password()
                //to enable password updates
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    //->dehydrateStateUsing(fn ($state) => \Hash::make($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))

                    ->dehydrated(fn ($state) => filled($state)) // Only update if filled
                    ->label('Password'),

                // ✅ Role selection dropdown
                // Note: 'landlord', 'superadmin', and 'user' are deliberately not assignable
                // here - 'landlord'/'superadmin' are only created via the signup flow /
                // superadmin panel, and 'user' is self-registered only, so staff can't
                // self-escalate or manufacture prospective-tenant accounts.
                // 'admin' is only offered to the landlord (property owner) - a staff
                // 'admin' account must not be able to mint peer admins. Also kept visible
                // when editing a record that's already 'admin' so the field doesn't blank
                // out from under an existing admin-staff editor. Actually enforced
                // server-side in CreateUser::beforeCreate()/EditUser::beforeSave(), since
                // hiding the option alone doesn't stop a tampered request.
                Select::make('role')
                    ->required()
                    ->label('User Role')
                    ->options(function (?\App\Models\User $record) {
                        $options = [
                            'manager' => 'Manager',
                            'caretaker' => 'Caretaker',
                            'agent' => 'Agent (BnB bookings)',
                            'tenant' => 'Tenant',
                        ];

                        if (auth()->user()?->role === 'landlord' || $record?->role === 'admin') {
                            $options = ['admin' => 'Admin'] + $options;
                        }

                        return $options;
                    })
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!in_array($state, ['manager', 'caretaker'])) {
                            $set('location_ids', []);
                        }
                        if ($state !== 'agent') {
                            $set('house_ids', []);
                        }
                    })
                    ->native(false), // optional: to use searchable dropdown

                // Assigned properties (only for manager/caretaker) - writes to the
                // staff_assignments pivot via CreateUser::afterCreate()/EditUser::afterSave(),
                // not a direct model attribute, since a staff member can hold more than one.
                Select::make('location_ids')
                    ->label('Assigned Properties')
                    ->multiple()
                    ->options(fn () => Location::pluck('location_name', 'id'))
                    ->required(fn (callable $get) => in_array($get('role'), ['manager', 'caretaker']))
                    ->visible(fn (callable $get) => in_array($get('role'), ['manager', 'caretaker']))
                    ->helperText('This staff member will only access resources in these properties')
                    ->dehydrated(false)
                    ->searchable()
                    ->preload()
                    ->native(false),

                // Assigned houses (agent only) - a specific short_term (BnB) unit, not a
                // whole property. Also writes to staff_assignments, via house_id instead
                // of location_id.
                Select::make('house_ids')
                    ->label('Assigned Houses (short-stay only)')
                    ->multiple()
                    ->options(fn () => \App\Models\House::where('listing_mode', 'short_term')->pluck('house_name', 'id'))
                    ->required(fn (callable $get) => $get('role') === 'agent')
                    ->visible(fn (callable $get) => $get('role') === 'agent')
                    ->helperText('This agent will only manage bookings for these houses')
                    ->dehydrated(false)
                    ->searchable()
                    ->preload()
                    ->native(false),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //column heads
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => \App\Support\AppNavigation::roleLabel($state))
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'primary',
                        'caretaker' => 'warning',
                        'agent' => 'success',
                        'tenant' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Location')
                    ->options(Location::pluck('location_name', 'id')->toArray())
                    ->query(fn (Builder $query, $value = null) => $query->when($value !== null, fn () => $query->whereRelation('tenant.house', 'location_id', $value))),

                Tables\Filters\SelectFilter::make('role')
                    ->label('User Type')
                    ->options([
                        'admin' => 'Admin',
                        'landlord' => 'Property Owner',
                        'manager' => 'Manager',
                        'caretaker' => 'Caretaker',
                        'agent' => 'Agent',
                        'tenant' => 'Tenant',
                    ])
                    ->query(fn (Builder $query, $value = null) => $query->when($value !== null, fn () => $query->where('role', $value))),
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

    /**
     * User carries no automatic landlord global scope (adding one risks infinite
     * recursion through SessionGuard::user()), so this Resource scopes explicitly -
     * the same pattern already used here for caretaker-location narrowing.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role !== 'superadmin') {
            $query->where('landlord_id', $user->landlord_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'sendNotification' => SendNotification::route('/send-notification'), // your custom page


        ];
    }

    /**
     * Role-based access control for Users resource.
     * Caretaker cannot access Users resource at all.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Only admin and landlord can access users resource
        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public static function canDeleteAny(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'landlord']);
    }
}
