<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MpesaC2bTransactionResource\Pages;
use App\Models\MpesaC2bTransaction;
use App\Models\Tenant;
use App\Services\MpesaC2bMatchService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

/**
 * The C2B Payments dashboard - every inbound Safaricom Paybill confirmation, matched
 * or not, with match_reason spelling out why. Mirrors MpesaTransactionResource's
 * conventions (list/view only, badge-colored status, StaffScope-narrowed query).
 */
class MpesaC2bTransactionResource extends Resource
{
    protected static ?string $model = MpesaC2bTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'C2B Payments';
    protected static ?string $navigationGroup = 'Payments';

    protected static function statusColor(string $state): string
    {
        return match ($state) {
            'matched_by_account', 'matched_by_phone', 'manually_matched' => 'success',
            'needs_review' => 'warning',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            //
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('channel.label')
                    ->label('Channel')
                    ->placeholder(fn (MpesaC2bTransaction $record) => $record->business_shortcode),
                TextColumn::make('bill_ref_number')
                    ->label('Account Number')
                    ->placeholder('(blank)')
                    ->searchable(),
                TextColumn::make('tenant.tenant_name')
                    ->label('Tenant')
                    ->placeholder('Unmatched')
                    ->searchable(),
                TextColumn::make('trans_amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('msisdn')
                    ->label('Phone')
                    ->copyable(),
                BadgeColumn::make('match_status')
                    ->label('Status')
                    ->color(fn (string $state) => static::statusColor($state))
                    ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('match_status')
                    ->label('Status')
                    ->options([
                        'matched_by_account' => 'Matched (account)',
                        'matched_by_phone' => 'Matched (phone)',
                        'manually_matched' => 'Manually matched',
                        'needs_review' => 'Needs review',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('assign_to_tenant')
                    ->label('Assign to tenant')
                    ->icon('heroicon-o-user-plus')
                    ->color('gray')
                    ->visible(fn (MpesaC2bTransaction $record) => $record->match_status === 'needs_review')
                    ->form([
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->options(fn (MpesaC2bTransaction $record) => Tenant::withoutGlobalScopes()
                                ->where('landlord_id', $record->landlord_id)
                                ->when($record->location_id, fn ($q) => $q->whereHas('house', fn ($h) => $h->where('location_id', $record->location_id)))
                                ->get()
                                ->mapWithKeys(fn (Tenant $t) => [$t->id => "{$t->tenant_name} ({$t->house?->publicName()})"]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (MpesaC2bTransaction $record, array $data, MpesaC2bMatchService $matcher) {
                        $tenant = Tenant::withoutGlobalScopes()->find($data['tenant_id']);

                        if (!$tenant) {
                            Notification::make()->danger()->title('Tenant not found')->send();
                            return;
                        }

                        $matcher->manuallyMatch($record, $tenant);

                        Notification::make()->success()->title('Payment assigned')->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Payment Details')
                    ->columns(2)
                    ->schema([
                        Components\TextEntry::make('trans_id')
                            ->label('M-Pesa TransID')
                            ->copyable(),
                        Components\TextEntry::make('trans_amount')
                            ->label('Amount')
                            ->formatStateUsing(fn ($state) => 'KES ' . number_format($state, 2)),
                        Components\TextEntry::make('business_shortcode')
                            ->label('Shortcode'),
                        Components\TextEntry::make('bill_ref_number')
                            ->label('Account Number entered'),
                        Components\TextEntry::make('msisdn')
                            ->label('Payer phone'),
                        Components\TextEntry::make('payer_name')
                            ->label('Payer name'),
                        Components\TextEntry::make('trans_time')
                            ->label('Paid at')
                            ->dateTime(),
                    ]),

                Components\Section::make('Matching')
                    ->columns(1)
                    ->schema([
                        Components\BadgeEntry::make('match_status')
                            ->label('Status')
                            ->color(fn (string $state) => static::statusColor($state))
                            ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),
                        Components\TextEntry::make('match_reason')
                            ->label('Reason'),
                        Components\TextEntry::make('tenant.tenant_name')
                            ->label('Matched tenant')
                            ->placeholder('None'),
                        Components\TextEntry::make('invoice.invoice_number')
                            ->label('Applied to invoice')
                            ->placeholder('None'),
                        Components\TextEntry::make('payment_id')
                            ->label('Payment record')
                            ->placeholder('Not yet credited'),
                    ]),

                Components\Section::make('Raw Payload')
                    ->schema([
                        Components\TextEntry::make('raw_payload')
                            ->label('')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->copyable(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMpesaC2bTransactions::route('/'),
            'view' => Pages\ViewMpesaC2bTransaction::route('/{record}'),
        ];
    }

    /**
     * Same visibility model as the sibling MpesaTransactionResource (STK) - no
     * canAccess() restriction, Manager/Caretaker narrowed to their assigned
     * properties via StaffScope, Agent denied entirely by that same helper.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return \App\Support\StaffScope::onRelation($query, 'tenant.house');
    }
}
