<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NoticeToVacateResource\Pages;
use App\Models\NoticeToVacate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NoticeToVacateResource extends Resource
{
    protected static ?string $model = NoticeToVacate::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'Notices to Vacate';
    protected static ?string $navigationGroup = 'Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tenant.tenant_name')
                    ->label('Tenant')
                    ->disabled(),
                Forms\Components\DatePicker::make('vacate_date')
                    ->label('Vacate Date')
                    ->disabled(),
                Forms\Components\TextInput::make('reason_type')
                    ->label('Reason')
                    ->disabled(),
                Forms\Components\Textarea::make('reason_text')
                    ->label('Details')
                    ->disabled(),
                Forms\Components\Textarea::make('admin_notes')
                    ->label('Admin Notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.tenant_name')
                    ->label('Tenant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.house.house_name')
                    ->label('House')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->state(function ($record) {
                        $balance = optional($record->tenant->latestPayment)->balance ?? 0;
                        if ($balance < 0) {
                            return '+' . number_format(abs($balance), 2);
                        }
                        return number_format($balance, 2);
                    })
                    ->prefix('KES ')
                    ->color(fn ($record) => match (true) {
                        optional($record->tenant->latestPayment)->balance > 0 => 'danger',
                        optional($record->tenant->latestPayment)->balance < 0 => 'warning',
                        default => 'success',
                    })
                    ->weight('bold')
                    ->sortable(query: function ($query, $direction) {
                        return $query->join('tenants', 'notice_to_vacates.tenant_id', '=', 'tenants.id')
                            ->leftJoin('payments as latest_payment', function ($join) {
                                $join->on('tenants.id', '=', 'latest_payment.tenant_id')
                                    ->whereRaw('latest_payment.id = (SELECT MAX(id) FROM payments WHERE tenant_id = tenants.id)');
                            })
                            ->orderBy('latest_payment.balance', $direction);
                    }),
                Tables\Columns\TextColumn::make('vacate_date')
                    ->label('Vacate Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason_type')
                    ->label('Reason')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'denied' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved On')
                    ->dateTime()
                    ->toggleable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'denied' => 'Denied',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-s-check')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Approval Notes'),
                    ])
                    ->action(function (NoticeToVacate $record, array $data) {
                        $record->approve($data['admin_notes'] ?? null);
                    })
                    ->visible(fn (NoticeToVacate $record) => $record->status === 'pending'),

                Tables\Actions\Action::make('deny')
                    ->label('Deny')
                    ->color('danger')
                    ->icon('heroicon-s-x-mark')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Denial Notes'),
                    ])
                    ->action(function (NoticeToVacate $record, array $data) {
                        $record->deny($data['admin_notes'] ?? null);
                    })
                    ->visible(fn (NoticeToVacate $record) => $record->status === 'pending'),
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
            'index' => Pages\ListNoticeToVacates::route('/'),
            'view' => Pages\ViewNoticeToVacate::route('/{record}'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Manager/Caretaker are narrowed to their assigned properties, Agent is denied
        // entirely - see StaffScope::onTenantChild() (this used to duplicate that logic
        // inline without the Agent deny-path, silently exposing every tenant's notices to
        // any Agent account with Filament access).
        return \App\Support\StaffScope::onTenantChild($query);
    }
}
