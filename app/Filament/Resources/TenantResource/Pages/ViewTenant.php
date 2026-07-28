<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Actions;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('message')
                ->label('Message on WhatsApp')
                ->icon('heroicon-s-chat-bubble-left')
                ->color('success')
                ->url(function () {
                    $phone = $this->record->phone_number;
                    // Remove any non-digit characters from phone
                    $phone = preg_replace('/\D/', '', $phone);
                    // Ensure it starts with country code (254 for Kenya)
                    if (!str_starts_with($phone, '254')) {
                        $phone = '254' . ltrim($phone, '0');
                    }
                    // Default message with tenant name
                    $message = "Hello {$this->record->tenant_name}, I have a message for you regarding ...";
                    return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
                })
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Tenant Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant_name')
                            ->label('Tenant Name'),
                        Infolists\Components\TextEntry::make('phone_number')
                            ->label('Phone Number'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('house.location.location_name')
                            ->label('Location'),
                        Infolists\Components\TextEntry::make('house.house_name')
                            ->label('House'),
                        Infolists\Components\TextEntry::make('house.rent_amount')
                            ->label('Monthly Rent')
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('date_admitted')
                            ->label('Date Admitted')
                            ->date(),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Portal Email')
                            ->default('Not linked'),
                    ])->columns(2),

                Infolists\Components\Section::make('Financial Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_invoiced')
                            ->label('Total Invoiced')
                            ->state(function ($record) {
                                return $record->invoices->sum('amount');
                            })
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('total_paid')
                            ->label('Total Paid')
                            ->state(function ($record) {
                                return $record->payments->sum('amount_paid');
                            })
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('current_balance')
                            ->label('Current Balance')
                            ->state(function ($record) {
                                $balance = $record->latestPayment->balance ?? 0;
                                return $balance;
                            })
                            ->money('KES')
                            ->color(function ($state) {
                                if ($state === null || $state == 0) {
                                    return 'success';
                                } elseif ($state < 0) {
                                    return 'warning'; // Overpaid
                                } else {
                                    return 'danger'; // Outstanding
                                }
                            }),
                        Infolists\Components\TextEntry::make('invoice_stats')
                            ->label('Invoice Status')
                            ->state(function ($record) {
                                $paid = $record->invoices->where('status', 'paid')->count();
                                $unpaid = $record->invoices->where('status', 'unpaid')->count();
                                $partial = $record->invoices->where('status', 'partial')->count();
                                return "Paid: {$paid} | Unpaid: {$unpaid} | Partial: {$partial}";
                            }),
                    ])->columns(2),

                Infolists\Components\Tabs::make('Details')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Invoices')
                            ->badge(fn ($record) => $record->invoices->count())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('invoices')
                                    ->label('Invoice History')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('invoice_number')
                                            ->label('Invoice #'),
                                        Infolists\Components\TextEntry::make('amount')
                                            ->label('Amount')
                                            ->money('KES'),
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'paid' => 'success',
                                                'unpaid' => 'danger',
                                                'partial' => 'warning',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('balance')
                                            ->label('Balance')
                                            ->money('KES'),
                                        Infolists\Components\TextEntry::make('invoice_date')
                                            ->label('Invoice Date')
                                            ->date(),
                                        Infolists\Components\TextEntry::make('due_date')
                                            ->label('Due Date')
                                            ->date(),
                                    ])
                                    ->columns(3)
                                    ->visible(fn ($record) => $record->invoices->count() > 0),

                                Infolists\Components\TextEntry::make('no_invoices')
                                    ->label('')
                                    ->default('No invoices found')
                                    ->visible(fn ($record) => $record->invoices->count() === 0),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Payments')
                            ->badge(fn ($record) => $record->payments->count())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('payments')
                                    ->label('Payment History')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('amount_paid')
                                            ->label('Amount Paid')
                                            ->money('KES'),
                                        Infolists\Components\TextEntry::make('payment_date')
                                            ->label('Payment Date')
                                            ->date(),
                                        Infolists\Components\TextEntry::make('payment_method')
                                            ->label('Method')
                                            ->badge()
                                            ->state(fn ($record) => $record->payment_method === 'mpesa' ? 'M-Pesa' : ($record->payment_method ?? 'Cash'))
                                            ->color(fn ($record) => $record->payment_method === 'mpesa' ? 'info' : 'gray'),
                                        Infolists\Components\TextEntry::make('reference')
                                            ->label('Reference'),
                                        Infolists\Components\TextEntry::make('invoice.invoice_number')
                                            ->label('Invoice #')
                                            ->default('N/A'),
                                        Infolists\Components\TextEntry::make('balance')
                                            ->label('Balance After')
                                            ->money('KES'),
                                    ])
                                    ->columns(6)
                                    ->visible(fn ($record) => $record->payments->count() > 0),

                                Infolists\Components\TextEntry::make('no_payments')
                                    ->label('')
                                    ->default('No payment records found')
                                    ->visible(fn ($record) => $record->payments->count() === 0),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Issues')
                            ->badge(fn ($record) => $record->issues->count())
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('issues')
                                    ->label('Reported Issues')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('title')
                                            ->label('Title'),
                                        Infolists\Components\TextEntry::make('description')
                                            ->label('Description')
                                            ->columnSpanFull(),
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'resolved' => 'success',
                                                'in_progress' => 'warning',
                                                'pending' => 'danger',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Reported')
                                            ->dateTime(),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->label('Last Updated')
                                            ->dateTime(),
                                    ])
                                    ->columns(3)
                                    ->visible(fn ($record) => $record->issues->count() > 0),

                                Infolists\Components\TextEntry::make('no_issues')
                                    ->label('')
                                    ->default('No issues reported')
                                    ->visible(fn ($record) => $record->issues->count() === 0),
                            ]),
                    ]),

                Infolists\Components\Section::make('Account Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created On')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }
}
