<?php

namespace App\Filament\Tenant\Resources;

use App\Models\NoticeToVacate;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NoticeToVacateResource extends Resource
{
    protected static ?string $model = NoticeToVacate::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'Notice to Vacate';

    // Without this, a tenant could view another tenant's notice by guessing/navigating
    // to /tenant/notice-to-vacates/{id} directly, since the ListNoticeToVacates page
    // scopes its table query but the resource's own base query (used to resolve single
    // records for the View page) did not.
    public static function getEloquentQuery(): Builder
    {
        $tenant = Auth::user()?->tenant;

        if (!$tenant) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('tenant_id', $tenant->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('vacate_date')
                    ->label('Date to Vacate')
                    ->native(false)
                    ->required(),

                Forms\Components\Select::make('reason_type')
                    ->label('Reason')
                    ->options([
                        'Relocation' => 'Relocation',
                        'Job Transfer' => 'Job Transfer',
                        'Rent Too High' => 'Rent Too High',
                        'Maintenance Issues' => 'Maintenance Issues',
                        'Better Offer' => 'Found Better Offer',
                        'Other' => 'Other (custom)',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\Textarea::make('reason_text')
                    ->label('Custom Reason')
                    ->helperText('Provide details if you selected Other')
                    ->rows(3)
                    ->visible(fn ($get) => $get('reason_type') === 'Other'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vacate_date')
                    ->label('Vacate Date')
                    ->date(),
                Tables\Columns\TextColumn::make('reason_type')
                    ->label('Reason')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'denied' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved On')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'denied' => 'Denied',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => NoticeToVacateResource\Pages\ListNoticeToVacates::route('/'),
            'create' => NoticeToVacateResource\Pages\CreateNoticeToVacate::route('/create'),
            'view' => NoticeToVacateResource\Pages\ViewNoticeToVacate::route('/{record}'),
        ];
    }
}
