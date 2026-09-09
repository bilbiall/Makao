<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ImportsLandlordData;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;

class PaymentImporter extends Importer
{
    use ImportsLandlordData;

    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('phone_number')
                ->label('Tenant phone number (must already exist - import Tenants first)')
                ->requiredMapping()
                ->rules(['required', 'max:50'])
                // Lookup-only - resolved to tenant_id/invoice_id in resolveRecord(),
                // neither is a real Payment column.
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('invoice_number')
                ->label('Invoice number (leave blank to apply to the tenant\'s most recent invoice)')
                ->ignoreBlankState()
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('amount_paid')
                ->label('Amount paid (KES)')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0.01']),

            ImportColumn::make('payment_date')
                ->requiredMapping()
                ->rules(['required', 'date']),

            ImportColumn::make('payment_method')
                ->label('Method (e.g. Cash, M-Pesa, Bank)')
                ->ignoreBlankState()
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('payment_reference')
                ->label('Reference / M-Pesa code (also prevents duplicate import on re-upload)')
                ->ignoreBlankState()
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('note')
                ->ignoreBlankState()
                ->rules(['nullable', 'max:255']),
        ];
    }

    public function resolveRecord(): Payment
    {
        $tenant = Tenant::where('phone_number', $this->data['phone_number'])->first();

        if (! $tenant) {
            throw new RowImportFailedException(
                "No tenant with phone number \"{$this->data['phone_number']}\" was found - import the Tenants sheet first."
            );
        }

        $invoice = filled($this->data['invoice_number'] ?? null)
            ? $tenant->invoices()->where('invoice_number', $this->data['invoice_number'])->first()
            : $tenant->invoices()->latest('invoice_date')->first();

        if (! $invoice) {
            throw new RowImportFailedException(
                "Tenant \"{$tenant->tenant_name}\" has no matching invoice to attach this payment to - import the Invoices sheet first."
            );
        }

        $payment = filled($this->data['payment_reference'] ?? null)
            ? Payment::firstOrNew(['payment_reference' => $this->data['payment_reference']])
            : new Payment();

        $payment->tenant_id = $tenant->id;
        $payment->invoice_id = $invoice->id;
        $payment->payment_type = $payment->payment_type ?: 'recorded';
        $payment->status = $payment->status ?: 'completed';

        return $payment;
    }
}
