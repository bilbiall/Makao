<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;


class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-s-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Select tenant (only those with unpaid or partial invoices)
            Select::make('tenant_id')
                ->label('Tenant')
                ->relationship('tenant', 'tenant_name')
                ->searchable()
                ->live()
                ->required(),

            // Show invoice number based on selected tenant
            Select::make('invoice_id')
                ->label('Invoice')
                ->options(function (callable $get) {
                    $tenantId = $get('tenant_id');
                    if (!$tenantId) return [];
                    return \App\Models\Invoice::where('tenant_id', $tenantId)
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->pluck('invoice_number', 'id');
                })
                ->required()
                ->live()
                ->afterStateUpdated(function (callable $get, callable $set, $state) {
                    // When an invoice is selected set expected_amount to the invoice total
                    $invoice = \App\Models\Invoice::find($state);
                    if ($invoice) {
                        $set('expected_amount', $invoice->amount);

                        // Prefer the latest payment's balance if one exists, otherwise fall back to invoice balance or amount
                        $latestPayment = \App\Models\Payment::where('invoice_id', $invoice->id)->latest()->first();
                        $currentBalance = $latestPayment?->balance ?? $invoice->balance ?? $invoice->amount;
                        $set('balance', $currentBalance);
                        // set balance after payment (current balance minus any entered amount_paid)
                        $amountPaid = $get('amount_paid') ?? 0;
                        $set('balance_after_payment', $currentBalance - $amountPaid);
                    }
                }),

            // Display expected amount (not editable, dynamically filled)
            TextInput::make('expected_amount')
                ->label('Expected Amount')
                ->disabled()
                ->numeric()
                ->reactive(), // 👈 Ensure it updates reactively

            // Show remaining balance from invoice (can be 0 or negative or positive)
            TextInput::make('balance')
                ->label('Remaining Balance (KES)')
                ->disabled()
                ->reactive()
                ->default(function (callable $get) {
                    $invoiceId = $get('invoice_id');
                    if (!$invoiceId) return null;

                    $invoice = \App\Models\Invoice::find($invoiceId);
                    if (! $invoice) return null;

                    $latestPayment = \App\Models\Payment::where('invoice_id', $invoiceId)->latest()->first();
                    return $latestPayment?->balance ?? $invoice->balance ?? $invoice->amount;
                }),

            // Computed field: shows what balance will be after this payment is applied
            TextInput::make('balance_after_payment')
                ->label('Balance After This Payment (KES)')
                ->disabled()
                ->reactive()
                ->default(function (callable $get) {
                    $currentBalance = $get('balance') ?? 0;
                    $amountPaid = $get('amount_paid') ?? 0;
                    return $currentBalance - $amountPaid;
                }),



            // Amount paid
            TextInput::make('amount_paid')
                ->label('Amount Paid')
                ->numeric()
                ->required()
                ->reactive()
                ->afterStateUpdated(function (callable $get, callable $set, $state) {
                    $currentBalance = $get('balance') ?? 0;
                    $amountPaid = $state ?? 0;
                    $set('balance_after_payment', $currentBalance - $amountPaid);
                }),

            // Payment reference - can select from M-Pesa transactions or enter manually
            Select::make('payment_reference')
                ->label('Payment Reference')
                ->options(function () {
                    return \App\Models\MpesaTransaction::whereIn('status', ['completed', 'success'])
                        ->whereDoesntHave('payment', function($query) {
                            $query->whereNotNull('id');
                        })
                        ->orderBy('created_at', 'desc')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function ($transaction) {
                            $label = $transaction->reference . ' (KES ' . number_format($transaction->amount, 2) . ' - ' . $transaction->created_at->format('M j, Y') . ')';
                            return [$transaction->reference => $label];
                        })
                        ->toArray();
                })
                ->searchable()
                ->createOptionForm([
                    TextInput::make('custom_reference')
                        ->label('Custom Reference')
                        ->required()
                ])
                ->createOptionUsing(function (array $data) {
                    return $data['custom_reference'];
                })
                ->placeholder('Select M-Pesa reference or create custom')
                ->required(),

            // Optional note
            Textarea::make('note')
                ->label('Note'),

            // Optional payment date
            /*DatePicker::make('payment_date')
                ->default(now())
                ->required(),*/
            DatePicker::make('payment_date')
                ->label('Payment Date')
                ->default(now())
                ->required(),

        ]);
                // Select tenant (only those with unpaid or partial invoices)
            /*Select::make('tenant_id')
                ->label('Tenant')
                ->relationship('tenant', 'tenant_name')
                ->searchable()
                ->live()
                ->required(),

            // Show invoice number based on selected tenant
            Select::make('invoice_id')
                ->label('Invoice')
                ->options(function (callable $get) {
                    $tenantId = $get('tenant_id');
                    if (!$tenantId) return [];
                    return \App\Models\Invoice::where('tenant_id', $tenantId)
                        ->whereIn('status', ['unpaid', 'partial'])
                        ->pluck('invoice_number', 'id');
                })
                ->required()
                ->live(),

            // Display expected amount (not editable)
            TextInput::make('expected_amount')
                ->label('Expected Amount')
                ->default(function (callable $get) {
                    $invoiceId = $get('invoice_id');
                    if (!$invoiceId) return null;
                    $invoice = \App\Models\Invoice::find($invoiceId);
                    return $invoice?->amount;
                })
                ->disabled(),

            // Amount paid
            TextInput::make('amount_paid')
                ->label('Amount Paid')
                ->numeric()
                ->required(),

            // Payment reference
            TextInput::make('payment_reference')
                ->label('Payment Reference')
                ->required(),

            // Optional note
            Textarea::make('note')
                ->label('Note'),

            // Optional payment date
            DatePicker::make('payment_date')
                ->default(now())
                ->required(),
            ]);*/
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('tenant.tenant_name')->label('Tenant')->searchable(),
                TextColumn::make('payment_reference')->label('Reference')->searchable(),
                TextColumn::make('invoice.invoice_number')->label('Invoice'),
                TextColumn::make('amount_paid')->money('KES'),
                TextColumn::make('payment_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'mpesa' => 'success',
                        'recorded' => 'info',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                //TextColumn::make('balance')->label('Balance')->money('KES'),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('KES') // optional, if you want currency formatting
                    ->sortable()
                    ->color(function ($state) {
                        if ($state == 0) {
                            return 'success'; // Green for cleared balance
                        } elseif ($state < 0) {
                            return 'warning'; // Yellow for overpayment
                        }
                        return 'danger'; // Red for unpaid
                    }),

                /*TextColumn::make('payment_date')->date(),*/
                TextColumn::make('payment_date')->label('Payment Date')->date(),

            ])
            ->filters([
                //by month
                Filter::make('payment_month')
                    ->label('Filter by Month & Year')
                    ->form([
                        DatePicker::make('month')->label('Pick Month'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['month'])) {
                            $query->whereMonth('payment_date', \Carbon\Carbon::parse($data['month'])->format('m'))
                                ->whereYear('payment_date', \Carbon\Carbon::parse($data['month'])->format('Y'));
                        }
                    }),

                SelectFilter::make('payment_type')
                    ->label('Payment Type')
                    ->options([
                        'mpesa' => 'M-Pesa',
                        'recorded' => 'Recorded',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    /**
     * Filter resources by caretaker's location
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Manager/Caretaker are narrowed to their assigned properties (staff_assignments pivot).
        if (\App\Support\StaffScope::isScopedStaff()) {
            $locationIds = \App\Support\StaffScope::locationIds();
            $query->whereHas('tenant.house', function ($q) use ($locationIds) {
                $q->whereIn('location_id', $locationIds);
            });
        }

        return $query;
    }
}
