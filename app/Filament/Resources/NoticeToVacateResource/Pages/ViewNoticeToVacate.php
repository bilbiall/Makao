<?php

namespace App\Filament\Resources\NoticeToVacateResource\Pages;

use App\Filament\Resources\NoticeToVacateResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewNoticeToVacate extends ViewRecord
{
    protected static string $resource = NoticeToVacateResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Notice Information')
                ->schema([
                    Infolists\Components\TextEntry::make('tenant.tenant_name')
                        ->label('Tenant'),
                    Infolists\Components\TextEntry::make('tenant.phone_number')
                        ->label('Phone'),
                    Infolists\Components\TextEntry::make('tenant.house.house_name')
                        ->label('House'),
                    Infolists\Components\TextEntry::make('tenant.house.location.location_name')
                        ->label('Location'),
                    Infolists\Components\TextEntry::make('vacate_date')
                        ->label('Vacate Date')
                        ->date(),
                    Infolists\Components\TextEntry::make('reason_type')
                        ->label('Reason')
                        ->badge(),
                    Infolists\Components\TextEntry::make('reason_text')
                        ->label('Custom Reason / Details')
                        ->columnSpanFull()
                        ->visible(fn ($record) => !empty($record->reason_text)),
                ])->columns(2),

            Infolists\Components\Section::make('Status')
                ->schema([
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'approved' => 'success',
                            'denied' => 'danger',
                            default => 'warning',
                        }),
                    Infolists\Components\TextEntry::make('approved_at')
                        ->label('Approved On')
                        ->dateTime()
                        ->visible(fn ($record) => !empty($record->approved_at)),
                    Infolists\Components\TextEntry::make('denied_at')
                        ->label('Denied On')
                        ->dateTime()
                        ->visible(fn ($record) => !empty($record->denied_at)),
                    Infolists\Components\TextEntry::make('approver.name')
                        ->label('Processed By')
                        ->visible(fn ($record) => !empty($record->approved_by)),
                    Infolists\Components\TextEntry::make('admin_notes')
                        ->label('Admin Notes')
                        ->columnSpanFull()
                        ->visible(fn ($record) => !empty($record->admin_notes)),
                ])->columns(2),

            Infolists\Components\Section::make('Financial Summary')
                ->schema([
                    Infolists\Components\TextEntry::make('total_invoiced')
                        ->label('Total Invoiced')
                        ->state(function ($record) {
                            return $record->tenant->invoices->sum('amount');
                        })
                        ->money('KES'),
                    Infolists\Components\TextEntry::make('total_paid')
                        ->label('Total Paid')
                        ->state(function ($record) {
                            return $record->tenant->payments->sum('amount_paid');
                        })
                        ->money('KES'),
                    Infolists\Components\TextEntry::make('current_balance')
                        ->label('Current Balance')
                        ->state(function ($record) {
                            $balance = $record->tenant->latestPayment->balance ?? 0;
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
                            $paid = $record->tenant->invoices->where('status', 'paid')->count();
                            $unpaid = $record->tenant->invoices->where('status', 'unpaid')->count();
                            $partial = $record->tenant->invoices->where('status', 'partial')->count();
                            return "Paid: {$paid} | Unpaid: {$unpaid} | Partial: {$partial}";
                        }),
                ])->columns(2),

            Infolists\Components\Tabs::make('Financial Details')
                ->tabs([
                    Infolists\Components\Tabs\Tab::make('Invoices')
                        ->badge(fn ($record) => $record->tenant->invoices->count())
                        ->schema([
                            Infolists\Components\RepeatableEntry::make('tenant.invoices')
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
                                ->columns(6)
                                ->visible(fn ($record) => $record->tenant->invoices->count() > 0),

                            Infolists\Components\TextEntry::make('no_invoices')
                                ->label('')
                                ->default('No invoices found')
                                ->visible(fn ($record) => $record->tenant->invoices->count() === 0),
                        ]),

                    Infolists\Components\Tabs\Tab::make('Payments')
                        ->badge(fn ($record) => $record->tenant->payments->count())
                        ->schema([
                            Infolists\Components\RepeatableEntry::make('tenant.payments')
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
                                ->visible(fn ($record) => $record->tenant->payments->count() > 0),

                            Infolists\Components\TextEntry::make('no_payments')
                                ->label('')
                                ->default('No payment records found')
                                ->visible(fn ($record) => $record->tenant->payments->count() === 0),
                        ]),
                ]),
        ]);
    }
}
