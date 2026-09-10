<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Support\StaffPermissions;
use App\Support\StaffScope;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Broadcast a message to tenants via SMS, Email, or both - see App\Models\Announcement.
 * Gated by StaffPermissions::SEND_ANNOUNCEMENTS so a landlord can delegate it through a
 * custom staff role.
 */
class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('subject')
                    ->label('Subject')
                    ->placeholder('e.g. Water shutdown this Friday')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(4)
                    ->helperText('Used as both the SMS text and the email body.'),

                Forms\Components\Select::make('location_id')
                    ->label('Send to')
                    ->relationship('location', 'location_name')
                    ->placeholder('All my properties')
                    ->helperText('Leave blank to reach every tenant across all your properties.')
                    ->searchable(),

                Forms\Components\Toggle::make('send_sms')
                    ->label('Send via SMS'),

                Forms\Components\Toggle::make('send_email')
                    ->label('Send via Email'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('location.location_name')
                    ->label('Sent to')
                    ->placeholder('All properties'),
                Tables\Columns\TextColumn::make('channels')
                    ->label('Channels')
                    ->getStateUsing(fn (Announcement $record) => collect([
                        $record->send_sms ? 'SMS' : null,
                        $record->send_email ? 'Email' : null,
                    ])->filter()->implode(' + ') ?: '—'),
                Tables\Columns\TextColumn::make('recipients_total')->label('Recipients'),
                Tables\Columns\TextColumn::make('sms_sent')
                    ->label('SMS sent/failed')
                    ->getStateUsing(fn (Announcement $record) => $record->send_sms ? "{$record->sms_sent}/{$record->sms_failed}" : '—'),
                Tables\Columns\TextColumn::make('email_sent')
                    ->label('Email sent/failed')
                    ->getStateUsing(fn (Announcement $record) => $record->send_email ? "{$record->email_sent}/{$record->email_failed}" : '—'),
                Tables\Columns\TextColumn::make('sentBy.name')->label('Sent by')->placeholder('—'),
                Tables\Columns\TextColumn::make('sent_at')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('sent_at', 'desc');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->hasPermission(StaffPermissions::SEND_ANNOUNCEMENTS);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // A scoped staff member (manager/caretaker restricted to specific properties)
        // shouldn't see the content of announcements sent to properties they don't
        // manage - matches the defensive scoping used throughout the rest of the app.
        if (StaffScope::isScopedStaff()) {
            $locationIds = StaffScope::locationIds();

            return $query->where(fn ($q) => $q->whereNull('location_id')->orWhereIn('location_id', $locationIds));
        }

        if (StaffScope::isAgent()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
        ];
    }
}
