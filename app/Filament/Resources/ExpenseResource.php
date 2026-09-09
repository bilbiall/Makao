<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Expenses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('expense_month')
                    ->label('Month')
                    ->displayFormat('F Y')
                    ->required(),

                Forms\Components\TextInput::make('electricity')->numeric()->prefix('KES')->default(0),
                Forms\Components\TextInput::make('water')->numeric()->prefix('KES')->default(0),
                Forms\Components\TextInput::make('internet')->numeric()->prefix('KES')->default(0),
                Forms\Components\TextInput::make('maintenance')->numeric()->prefix('KES')->default(0),
                Forms\Components\TextInput::make('other')->label('Other expenses')->numeric()->prefix('KES')->default(0),

                Forms\Components\TextInput::make('notes')->maxLength(255)->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_month')->label('Month')->date('F Y')->sortable(),
                Tables\Columns\TextColumn::make('electricity')->money('KES'),
                Tables\Columns\TextColumn::make('water')->money('KES'),
                Tables\Columns\TextColumn::make('internet')->money('KES'),
                Tables\Columns\TextColumn::make('maintenance')->money('KES'),
                Tables\Columns\TextColumn::make('other')->label('Other')->money('KES'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->state(fn (Expense $record) => $record->total())
                    ->money('KES')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('notes')->limit(30)->toggleable(),
            ])
            ->defaultSort('expense_month', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Same visibility as LocationResource - operational/financial data, not for Manager/Caretaker/Agent. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'landlord']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
