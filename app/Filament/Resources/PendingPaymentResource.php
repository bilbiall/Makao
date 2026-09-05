<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingPaymentResource\Pages;
use App\Models\PendingPayment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class PendingPaymentResource extends Resource
{
    protected static ?string $model = PendingPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Pending Payments';
    protected static ?string $navigationGroup = 'Payments';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('reference')->label('Reference')->searchable()->wrap(),
                TextColumn::make('tenant.tenant_name')->label('Tenant')->searchable()->wrap(),
                TextColumn::make('tenant.house.house_name')->label('House')->wrap()->sortable(),
                TextColumn::make('amount')->label('Amount (KES)')->money('KES'),
                TextColumn::make('status')->label('Status')->badge()->colors([
                    'warning' => 'pending',
                    'success' => 'completed',
                    'danger' => 'failed',
                ]),
                TextColumn::make('created_at')->label('Created')->dateTime()->sortable(),
                TextColumn::make('tenant.balance')->label('Tenant Balance')->money('KES')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ])
                    ->label('Status'),

                Filter::make('tenant_balance_status')
                    ->label('Tenant Balance')
                    ->form([
                        Forms\Components\Select::make('balance_status')
                            ->options([
                                'overpaid' => 'Overpaid (negative balance)',
                                'zero' => 'Zero balance',
                                'due' => 'Due (positive balance)',
                            ])
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['balance_status'])) {
                            return;
                        }
                        if ($data['balance_status'] === 'overpaid') {
                            $query->whereHas('tenant', fn($q) => $q->where('balance', '<', 0));
                        } elseif ($data['balance_status'] === 'zero') {
                            $query->whereHas('tenant', fn($q) => $q->where('balance', 0));
                        } else {
                            $query->whereHas('tenant', fn($q) => $q->where('balance', '>', 0));
                        }
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->url(fn (PendingPayment $record) => url(config('filament.path', 'admin') . '/resources/pending-payments/' . $record->id))
                    ->openUrlInNewTab(false),

                Action::make('mark_completed')
                    ->label('Mark Completed')
                    ->requiresConfirmation()
                    ->action(function (PendingPayment $record) {
                        // Create a Payment record if an invoice exists
                        if ($record->invoice_id) {
                            \App\Models\Payment::create([
                                'tenant_id' => $record->tenant_id,
                                'invoice_id' => $record->invoice_id,
                                'amount_paid' => $record->amount,
                                'payment_reference' => $record->reference ?? \Illuminate\Support\Str::uuid()->toString(),
                                'payment_date' => now(),
                                'note' => 'Manually confirmed by admin (PendingPayment ID: ' . $record->id . ')',
                            ]);
                        }
                        $record->status = 'completed';
                        $record->save();
                    }),

                Action::make('mark_failed')
                    ->label('Mark Failed')
                    ->requiresConfirmation()
                    ->action(function (PendingPayment $record) {
                        $record->status = 'failed';
                        $record->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingPayments::route('/'),
            'view' => Pages\ViewPendingPayment::route('/{record}'),
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
        // inline without the Agent deny-path, silently exposing every tenant's pending
        // payments to any Agent account with Filament access).
        return \App\Support\StaffScope::onTenantChild($query);
    }
}
