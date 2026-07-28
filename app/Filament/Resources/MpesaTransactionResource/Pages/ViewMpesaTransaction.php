<?php

namespace App\Filament\Resources\MpesaTransactionResource\Pages;

use App\Filament\Resources\MpesaTransactionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewMpesaTransaction extends ViewRecord
{
    protected static string $resource = MpesaTransactionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaction Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Reference'),
                        Infolists\Components\TextEntry::make('checkout_request_id')
                            ->label('Checkout Request ID')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('tenant.tenant_name')
                            ->label('Tenant'),
                        Infolists\Components\TextEntry::make('phone_number')
                            ->label('Phone Number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('invoice.invoice_number')
                            ->label('Invoice Number'),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Amount')
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Transaction Date')
                            ->dateTime(),
                    ])->columns(2),

                Infolists\Components\Section::make('Transaction Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state) => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'timeout' => 'gray',
                                default => 'info',
                            }),
                        Infolists\Components\TextEntry::make('receipt_number')
                            ->label('Receipt Number')
                            ->visible(fn($record) => $record->status === 'completed' && !empty($record->receipt_number)),
                        Infolists\Components\TextEntry::make('response_code')
                            ->label('Response Code'),
                        Infolists\Components\TextEntry::make('response_message')
                            ->label('Response Message'),
                        Infolists\Components\TextEntry::make('result_desc')
                            ->label('Failure Reason')
                            ->visible(fn($record) => in_array($record->status, ['failed', 'timeout'])),
                    ])->columns(2),

                Infolists\Components\Section::make('Detailed Status Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('status_info')
                            ->label('Status Details')
                            ->state(function ($record) {
                                return match($record->status) {
                                    'completed' => "Transaction successful\nReceipt: {$record->receipt_number}\nPayment confirmed on " . ($record->updated_at?->format('d M Y H:i') ?? 'N/A'),
                                    'pending' => "Transaction is pending\nPlease wait for completion",
                                    'failed' => "Transaction failed\nReason: {$record->result_desc}\nPlease check your account and try again",
                                    'timeout' => "Transaction timed out\nReason: Request timeout\nPlease retry the transaction",
                                    default => "Transaction status: {$record->status}",
                                };
                            })
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('API Response Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('result_code')
                            ->label('Result Code'),
                        Infolists\Components\TextEntry::make('meta')
                            ->label('Metadata')
                            ->state(function ($record) {
                                return json_encode($record->meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            })
                            ->copyable(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])->columns(1),

                Infolists\Components\Section::make('Related Payment')
                    ->visible(fn($record) => $record->status === 'completed')
                    ->schema([
                        Infolists\Components\TextEntry::make('related_payment')
                            ->label('Payment Record')
                            ->state(function ($record) {
                                $payment = $record->tenant?->payments()
                                    ->where('reference', $record->receipt_number)
                                    ->orWhere('reference', $record->reference)
                                    ->first();
                                return $payment ? "Payment recorded as {$payment->amount_paid} KES on {$payment->payment_date?->format('d M Y')}" : 'Not yet recorded in system';
                            }),
                    ]),
            ]);
    }
}
