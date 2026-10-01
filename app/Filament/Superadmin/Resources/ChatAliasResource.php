<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\ChatAliasResource\Pages;
use App\Models\ChatAlias;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Phrases taught to the chat assistant - nicknames for areas ("Kasa"),
 * Swahili/Sheng words for yes/no, unit-type phrases, and ways of asking for a
 * person. Read by ChatAliasService; changes apply within a few minutes.
 */
class ChatAliasResource extends Resource
{
    protected static ?string $model = ChatAlias::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationGroup = 'Chat assistant';

    protected static ?string $navigationLabel = 'Taught phrases';

    protected static ?string $modelLabel = 'phrase';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->options(ChatAlias::TYPES)->required()->live(),

            Forms\Components\TextInput::make('phrase')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, Forms\Get $get) => $rule->where('type', $get('type')))
                ->helperText('What a visitor types. Not case sensitive.'),

            Forms\Components\Select::make('canonical')
                ->label('Means')
                ->searchable()
                ->options(fn (Forms\Get $get) => ChatAlias::canonicalOptions($get('type')))
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['area', 'house_type'], true))
                ->required(fn (Forms\Get $get) => in_array($get('type'), ['area', 'house_type'], true)),

            Forms\Components\Select::make('language')->options(['sw' => 'Swahili / Sheng', 'en' => 'English']),

            Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('uses_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('phrase')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->formatStateUsing(fn (string $state) => ChatAlias::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('canonical')->label('Means')->placeholder('-'),
                Tables\Columns\TextColumn::make('language')->badge()->placeholder('-'),
                Tables\Columns\TextColumn::make('uses_count')->label('Times used')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(ChatAlias::TYPES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatAliases::route('/'),
            'create' => Pages\CreateChatAlias::route('/create'),
            'edit' => Pages\EditChatAlias::route('/{record}/edit'),
        ];
    }
}
