<?php

namespace App\Filament\Imports\Concerns;

use App\Support\ImportContext;
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

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import complete: ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed - download the failure CSV for details.';
        }

        return $body;
    }
}
