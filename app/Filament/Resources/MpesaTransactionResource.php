<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MpesaTransactionResource\Pages;
use App\Models\MpesaTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Builder;

class MpesaTransactionResource extends Resource
{
    protected static ?string $model = MpesaTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationLabel = 'M-Pesa Transactions';
    protected static ?string $navigationGroup = 'Payments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.tenant_name')
                    ->label('Tenant')
                    ->searchable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->copyable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn(string $state) => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'timeout' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('receipt_number')
                    ->label('Receipt'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'timeout' => 'Timeout',
                    ]),
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'tenant_name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
                Components\Section::make('Transaction Details')
                    ->columns(2)
                    ->schema([
                        Components\TextEntry::make('reference')
                            ->label('Reference'),
                        Components\TextEntry::make('checkout_request_id')
                            ->label('Checkout Request ID')
                            ->copyable(),
                        Components\TextEntry::make('tenant.tenant_name')
                            ->label('Tenant'),
                        Components\TextEntry::make('invoice.invoice_number')
                            ->label('Invoice'),
                        Components\TextEntry::make('amount')
                            ->label('Amount')
                            ->formatStateUsing(fn ($state) => 'KES ' . number_format($state, 2)),
                        Components\TextEntry::make('phone_number')
                            ->label('Phone Number'),
                        Components\BadgeEntry::make('status')
                            ->label('Status')
                            ->color(fn(string $state) => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'timeout' => 'gray',
                                default => 'info',
                            }),
                        Components\TextEntry::make('receipt_number')
                            ->label('Receipt Number'),
                    ]),

                Components\Section::make('Response Details')
                    ->columns(1)
                    ->schema([
                        Components\TextEntry::make('response_code')
                            ->label('Response Code'),
                        Components\TextEntry::make('response_message')
                            ->label('Response Message'),
                        Components\TextEntry::make('result_code')
                            ->label('Result Code'),
                        Components\TextEntry::make('result_desc')
                            ->label('Result Description'),
                    ]),

                Components\Section::make('Metadata')
                    ->schema([
                        Components\TextEntry::make('meta')
                            ->label('Meta Data')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->copyable(),
                    ]),

                Components\Section::make('Timestamps')
                    ->columns(2)
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMpesaTransactions::route('/'),
            'view' => Pages\ViewMpesaTransaction::route('/{record}'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Caretakers can only see M-Pesa transactions for invoices in their assigned location
        if ($user && $user->role === 'caretaker' && $user->location_id) {
            $query->whereHas('invoice.tenant.house', function ($q) use ($user) {
                $q->where('location_id', $user->location_id);
            });
        }

        return $query;
    }
}
