<?php

namespace App\Filament\Resources\DeletedTenantResource\Pages;

use App\Filament\Resources\DeletedTenantResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewDeletedTenant extends ViewRecord
{
    protected static string $resource = DeletedTenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions can be added here
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
                        Infolists\Components\TextEntry::make('id_number')
                            ->label('ID Number'),
                        Infolists\Components\TextEntry::make('location_name')
                            ->label('Location'),
                        Infolists\Components\TextEntry::make('previous_house')
                            ->label('Previous House'),
                    ])->columns(2),

                Infolists\Components\Section::make('Financial Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_invoiced')
                            ->label('Total Invoiced')
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('total_paid')
                            ->label('Total Paid')
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('outstanding_balance')
                            ->label('Outstanding Balance')
                            ->money('KES')
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('overpayment')
                            ->label('Overpayment')
                            ->money('KES')
                            ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    ])->columns(2),

                Infolists\Components\Tabs::make('Details')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Invoices')
                            ->badge(fn ($record) => $record->invoices_count)
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('invoices_count')
                                            ->label('Total Invoices'),
                                        Infolists\Components\TextEntry::make('paid_invoices_count')
                                            ->label('Paid Invoices')
                                            ->color('success'),
                                        Infolists\Components\TextEntry::make('unpaid_invoices_count')
                                            ->label('Unpaid Invoices')
                                            ->color('danger'),
                                        Infolists\Components\TextEntry::make('partial_invoices_count')
                                            ->label('Partial Invoices')
                                            ->color('warning'),
                                    ])->columns(4),

                                Infolists\Components\RepeatableEntry::make('invoices_data')
                                    ->label('Invoice Details')
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
                                        Infolists\Components\TextEntry::make('payments_received')
                                            ->label('Paid')
                                            ->money('KES'),
                                        Infolists\Components\TextEntry::make('invoice_date')
                                            ->label('Invoice Date')
                                            ->date(),
                                        Infolists\Components\TextEntry::make('due_date')
                                            ->label('Due Date')
                                            ->date(),
                                    ])
                                    ->columns(3)
                                    ->visible(fn ($record) => !empty($record->invoices_data)),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Payments')
                            ->badge(fn ($record) => count($record->payments_data ?? []))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('payments_data')
                                    ->label('Payment Details')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('amount_paid')
                                            ->label('Amount Paid')
                                            ->money('KES'),
                                        Infolists\Components\TextEntry::make('payment_date')
                                            ->label('Payment Date')
                                            ->date(),
                                        Infolists\Components\TextEntry::make('reference')
                                            ->label('Reference'),
                                        Infolists\Components\TextEntry::make('invoice_number')
                                            ->label('Invoice #'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($record) => !empty($record->payments_data)),

                                Infolists\Components\TextEntry::make('no_payments')
                                    ->label('')
                                    ->default('No payment records found')
                                    ->visible(fn ($record) => empty($record->payments_data)),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Issues')
                            ->badge(fn ($record) => $record->issues_count)
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('issues_data')
                                    ->label('Issue Details')
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
                                    ->visible(fn ($record) => !empty($record->issues_data)),

                                Infolists\Components\TextEntry::make('no_issues')
                                    ->label('')
                                    ->default('No issues reported')
                                    ->visible(fn ($record) => empty($record->issues_data)),
                            ]),
                    ]),

                Infolists\Components\Section::make('Archival Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('deleted_at')
                            ->label('Deleted On')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('auto_delete_at')
                            ->label('Auto-Delete On')
                            ->dateTime()
                            ->color(fn ($state) => $state->diffInDays(now()) <= 7 ? 'danger' : 'warning'),
                    ])->columns(2),
            ]);
    }
}
