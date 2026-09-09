<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ImportsLandlordData;
use App\Models\Invoice;
use App\Models\Tenant;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;

class InvoiceImporter extends Importer
{
    use ImportsLandlordData;

    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('phone_number')
                ->label('Tenant phone number (must already exist - import Tenants first)')
                ->requiredMapping()
                ->rules(['required', 'max:50'])
                // Lookup-only - resolved to tenant_id in resolveRecord(), not a real
                // Invoice column.
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('invoice_number')
                ->label('Invoice number (leave blank to auto-generate)')
                ->ignoreBlankState()
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('invoice_date')
                ->requiredMapping()
                ->rules(['required', 'date']),

            ImportColumn::make('due_date')
                ->requiredMapping()
                ->rules(['required', 'date']),

            ImportColumn::make('amount')
                ->label('Amount (KES)')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('status')
                ->label('Status (unpaid/partial/paid)')
                ->ignoreBlankState()
                ->rules(['nullable', 'in:unpaid,partial,paid']),

            ImportColumn::make('balance')
                ->label('Balance still owed (KES, leave blank to default to the full amount)')
                ->numeric()
                ->ignoreBlankState()
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('comment')
                ->ignoreBlankState()
                ->rules(['nullable', 'max:255']),
        ];
    }

    public function resolveRecord(): Invoice
    {
        $tenant = Tenant::where('phone_number', $this->data['phone_number'])->first();

        if (! $tenant) {
            throw new RowImportFailedException(
                "No tenant with phone number \"{$this->data['phone_number']}\" was found - import the Tenants sheet first."
            );
        }

        $invoice = filled($this->data['invoice_number'] ?? null)
            ? Invoice::firstOrNew([
                'tenant_id' => $tenant->id,
                'invoice_number' => $this->data['invoice_number'],
            ])
            : new Invoice();

        $invoice->tenant_id = $tenant->id;

        return $invoice;
    }
}
