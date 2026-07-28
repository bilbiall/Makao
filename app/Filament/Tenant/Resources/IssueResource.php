<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\IssueResource\Pages;
use App\Filament\Tenant\Resources\IssueResource\RelationManagers;
use App\Models\Issue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

//restrict tenant
use Illuminate\Support\Facades\Auth;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static ?string $navigationIcon = 'heroicon-s-flag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //adding new issue
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->required(),

                // We hide status from tenant creation form
                Forms\Components\TextInput::make('status')
                    ->disabled()
                    ->visibleOn('view'), // Only visible in View page

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //views
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('description')->limit(50),
                //Tables\Columns\TextColumn::make('status'),
                //cooler look for status
                Tables\Columns\TextColumn::make('status')
                    ->badge() // Show as a badge
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',       // red
                        'in_progress' => 'warning', // yellow
                        'resolved' => 'success',    // green
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')->label('Date'),

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

    //restrict tenant to their issues
    public static function getEloquentQuery(): Builder
{
    $tenant = Auth::user()?->tenant;

    if (!$tenant) {
        // Prevent access if tenant is not set
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    return parent::getEloquentQuery()
        ->where('tenant_id', $tenant->id);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
        ];
    }
}
