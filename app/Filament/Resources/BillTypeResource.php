<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillTypeResource\Pages;
use App\Models\BillType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * A landlord's own catalog of bill/charge types (Water, Trash, ...) - see
 * App\Models\BillType. Gated by StaffPermissions::MANAGE_BILL_TYPES so a landlord can
 * choose to delegate it via a custom staff role - always available to admin/landlord
 * (owner-tier), and to legacy manager/caretaker accounts by default (same as every
 * other catalog permission - see StaffPermissions::defaultFor()).
 */
class BillTypeResource extends Resource
{
    protected static ?string $model = BillType::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Bill Types';

    protected static ?string $navigationGroup = 'Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->placeholder('e.g. Water, Trash, Security')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('location_id')
                    ->label('Applies to')
                    ->relationship('location', 'location_name')
                    ->placeholder('All my properties')
                    ->helperText('Leave blank to make this available for any of your properties.')
                    ->searchable(),

                Forms\Components\TextInput::make('default_amount')
                    ->label('Default amount (KES)')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Prefills this amount when adding a manual bill, and is the exact amount used every month if marked recurring.'),

                Forms\Components\Toggle::make('is_recurring')
                    ->label('Recurring - auto-bill every month')
                    ->helperText('Automatically added to every tenant in the property/properties above, every month, alongside rent.')
                    ->live(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Turn off to stop offering this type on new bills without deleting its history.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('location.location_name')
                    ->label('Applies to')
                    ->placeholder('All properties'),
                Tables\Columns\TextColumn::make('default_amount')
                    ->label('Default amount')
                    ->money('KES')
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Bills using it')
                    ->counts('items'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (BillType $record, Tables\Actions\DeleteAction $action) {
                        if ($record->items()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('This type has bills recorded against it')
                                ->body('Deactivate it instead of deleting, so past bills keep their breakdown.')
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

        return $user && $user->hasPermission(\App\Support\StaffPermissions::MANAGE_BILL_TYPES);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('landlord_id', auth()->user()?->landlord_id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBillTypes::route('/'),
            'create' => Pages\CreateBillType::route('/create'),
            'edit' => Pages\EditBillType::route('/{record}/edit'),
        ];
    }
}
