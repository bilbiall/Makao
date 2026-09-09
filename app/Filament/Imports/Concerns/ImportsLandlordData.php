<?php

namespace App\Filament\Imports\Concerns;

use App\Support\ImportContext;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;

/**
 * Shared behaviour for every property-manager data importer (Location, House,
 * Tenant, Invoice, Payment, Expense):
 *
 * - Wraps each row in ImportContext::run() so the models being written to know
 *   to skip SMS/notification side effects meant for a real-time event, not a
 *   bulk historical data load.
 * - Runs inline (no queue worker runs in this deployment, so a queued job
 *   would otherwise sit pending forever in the `jobs` table).
 */
trait ImportsLandlordData
{
    public function __invoke(array $data): void
    {
        ImportContext::run(fn () => parent::__invoke($data));
    }

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    /**
     * Some models (House, Tenant, Location) silently cancel their own save() -
     * returning false, no exception - when a landlord's plan limit blocks adding
     * one more (see PackageLimitService). Importer::saveRecord() ignores that
     * return value, which would otherwise count a row that was never actually
     * written as a "successful" import. Surface it as a real per-row failure
     * instead, in both UIs.
     */
    public function saveRecord(): void
    {
        $this->record->save();

        if (! $this->record->exists) {
            throw new RowImportFailedException(
                'Could not be saved - check this landlord\'s plan limits (properties/units/tenants) haven\'t been reached.'
            );
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import complete: ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed - download the failure CSV for details.';
        }

        return $body;
    }
}
