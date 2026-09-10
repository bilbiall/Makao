<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffRoleResource\Pages;
use App\Models\StaffRole;
use App\Support\StaffPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Landlord-defined custom staff roles (name + scope type + checkbox
 * permissions) - see App\Support\StaffPermissions for the fixed permission
 * catalog and App\Models\StaffRole for how a User gets assigned one instead
 * of the built-in manager/caretaker/agent roles.
 */
class StaffRoleResource extends Resource
{
    protected static ?string $model = StaffRole::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Staff Roles';

    protected static ?string $navigationGroup = 'Accounts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Role name')
                    ->placeholder('e.g. Front Desk')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Radio::make('scope_type')
                    ->label('Scope')
                    ->options([
                        'location' => 'Property-based - like Manager/Caretaker, sees everything in their assigned properties',
                        'house' => 'Unit-based - like Agent, sees only their directly assigned units',
                    ])
                    ->required()
                    ->default('location'),

                Forms\Components\CheckboxList::make('permissions')
                    ->label('Permissions')
                    ->options(static::permissionOptions())
                    ->columns(2)
                    ->bulkToggleable()
                    ->helperText('What staff assigned this role are allowed to do. Leave everything unchecked for a view-only role.'),
            ]);
    }

    /** Flattened, group-prefixed options for the single checkbox list (Filament's CheckboxList has no native grouped-options support). */
    protected static function permissionOptions(): array
    {
        $options = [];

        foreach (StaffPermissions::catalog() as $group => $slugs) {
            foreach ($slugs as $slug => $label) {
                $options[$slug] = "{$group}: {$label}";
            }
        }

        return $options;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('scope_type')
                    ->label('Scope')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'house' ? 'Unit-based' : 'Property-based')
                    ->color(fn (string $state) => $state === 'house' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Staff assigned')
                    ->counts('users'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (StaffRole $record, Tables\Actions\DeleteAction $action) {
                        if ($record->users()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Reassign staff before deleting this role')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->defaultSort('name');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('landlord_id', auth()->user()?->landlord_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffRoles::route('/'),
            'create' => Pages\CreateStaffRole::route('/create'),
            'edit' => Pages\EditStaffRole::route('/{record}/edit'),
        ];
    }
}
