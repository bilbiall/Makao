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
                // Note: 'landlord' and 'superadmin' are deliberately not assignable here -
                // they're only created via the signup flow / superadmin panel, so staff can't self-escalate.
                Select::make('role')
                    ->required()
                    ->label('User Role')
                    ->options([
                        'admin' => 'Admin',
                        'caretaker' => 'Caretaker',
                        'tenant' => 'Tenant',
                    ])
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => 
                        $state !== 'caretaker' ? $set('location_id', null) : null
                    )
                    ->native(false), // optional: to use searchable dropdown

                // Location selection (only for caretakers)
                Select::make('location_id')
                    ->label('Assigned Location')
                    ->relationship('location', 'location_name')
                    ->required(fn (callable $get) => $get('role') === 'caretaker')
                    ->visible(fn (callable $get) => $get('role') === 'caretaker')
                    ->helperText('Caretaker will only access resources in this location')
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
                    ->color(fn(string $state): string => match ($state) {
                        'admin' => 'danger',
                        'caretaker' => 'warning',
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
                        'landlord' => 'Landlord',
                        'caretaker' => 'Caretaker',
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
