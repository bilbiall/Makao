<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\ChatTurnResource\Pages;
use App\Models\ChatAlias;
use App\Models\ChatTurn;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

/**
 * The chat "failure inbox": every logged visitor message, defaulting to the
 * ones the assistant couldn't really answer. Each can be turned into a taught
 * alias in one click (the part that makes the assistant improve), or ignored.
 * Messages are stored masked and pruned after 90 days.
 */
class ChatTurnResource extends Resource
{
    protected static ?string $model = ChatTurn::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Chat assistant';

    protected static ?string $navigationLabel = 'Chat failure inbox';

    protected static ?string $modelLabel = 'chat message';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = ChatTurn::needsAttention()->count();
        } catch (\Throwable) {
            return null;
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('When')->since()->sortable(),
                Tables\Columns\TextColumn::make('user_text')->label('Visitor wrote')->wrap()->searchable()->limit(120),
                Tables\Columns\TextColumn::make('language')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'sw' ? 'Swahili' : ($state === 'en' ? 'English' : '-')),
                Tables\Columns\TextColumn::make('branch')->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'results', 'narrow', 'alternatives_shown' => 'success',
                        'none', 'clarify', 'property_not_found' => 'danger',
                        'zero_results' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('offer_result')->label('Offer')->badge()->placeholder('-')
                    ->color(fn (?string $state) => $state === 'accepted' ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('handoff_shown')->label('Sent to team')->boolean(),
                Tables\Columns\TextColumn::make('status')->badge()->placeholder('new'),
            ])
            ->filters([
                Tables\Filters\Filter::make('needs_attention')
                    ->label('Needs attention')
                    ->default()
                    ->query(fn ($query) => $query->needsAttention()),
                Tables\Filters\SelectFilter::make('language')->options(['en' => 'English', 'sw' => 'Swahili']),
                Tables\Filters\SelectFilter::make('branch')->options(fn () => ChatTurn::query()->distinct()->pluck('branch', 'branch')->filter()->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('teach')
                    ->label('Teach')
                    ->icon('heroicon-o-academic-cap')
                    ->modalHeading('Teach the assistant a phrase')
                    ->modalDescription('Next time a visitor uses this phrase, the assistant will understand it. Takes effect within a few minutes.')
                    ->form([
                        Forms\Components\Select::make('type')->options(ChatAlias::TYPES)->required()->live(),
                        Forms\Components\TextInput::make('phrase')->required()->maxLength(255)
                            ->default(fn (ChatTurn $record) => $record->user_text)
                            ->helperText('Trim this down to just the word or phrase to learn, e.g. "kasa".'),
                        Forms\Components\Select::make('canonical')->label('Means')->searchable()
                            ->options(fn (Forms\Get $get) => ChatAlias::canonicalOptions($get('type')))
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['area', 'house_type'], true))
                            ->required(fn (Forms\Get $get) => in_array($get('type'), ['area', 'house_type'], true)),
                        Forms\Components\Select::make('language')->options(['sw' => 'Swahili / Sheng', 'en' => 'English'])
                            ->default(fn (ChatTurn $record) => $record->language),
                    ])
                    ->action(function (ChatTurn $record, array $data) {
                        ChatAlias::updateOrCreate(
                            ['type' => $data['type'], 'phrase' => mb_strtolower(trim($data['phrase']))],
                            ['canonical' => $data['canonical'] ?? null, 'language' => $data['language'] ?? null, 'is_active' => true],
                        );

                        $record->update(['status' => 'taught']);

                        Notification::make()->success()->title('Phrase learned')->send();
                    }),

                Tables\Actions\Action::make('thread')
                    ->label('Conversation')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (ChatTurn $record): View => view('filament.superadmin.chat-thread', [
                        'turns' => ChatTurn::where('chat_id', $record->chat_id)->orderBy('id')->get(),
                        'highlight' => $record->id,
                    ])),

                Tables\Actions\Action::make('ignore')
                    ->label('Ignore')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->visible(fn (ChatTurn $record) => $record->status === null)
                    ->action(fn (ChatTurn $record) => $record->update(['status' => 'ignored'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('ignore')
                    ->label('Ignore selected')
                    ->icon('heroicon-o-eye-slash')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn ($records) => $records->each->update(['status' => 'ignored'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatTurns::route('/'),
        ];
    }
}
