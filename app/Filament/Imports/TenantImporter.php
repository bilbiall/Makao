<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ImportsLandlordData;
use App\Models\House;
use App\Models\Tenant;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;

/**
 * A House has at most one active Tenant (see House::tenant() / Tenant::house()),
 * so this always resolves to that unit's single current tenant row - import the
 * Houses sheet first so the unit exists to admit a tenant into.
 */
class TenantImporter extends Importer
{
    use ImportsLandlordData;

    protected static ?string $model = Tenant::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('location_name')
                ->label('Property / building name (helps find the unit)')
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('house_name')
                ->label('Unit name/number (must already exist - import Units first)')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                // Lookup-only - resolved to house_id in resolveRecord(), not a real
                // Tenant column.
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('tenant_name')
                ->label('Tenant full name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('phone_number')
                ->requiredMapping()
                ->rules(['required', 'max:50']),

            ImportColumn::make('email')
                // tenants.email is NOT NULL with no default - always fill it (blank
                // becomes '', which is a valid non-null value) rather than skipping
                // the column on a blank cell.
                ->castStateUsing(fn ($state) => $state ?? '')
                ->rules(['nullable', 'email', 'max:255']),

            ImportColumn::make('date_admitted')
                ->label('Move-in date')
                ->ignoreBlankState()
                ->rules(['nullable', 'date']),

            ImportColumn::make('balance')
                ->label('Opening balance (KES, negative if tenant is in credit)')
                ->numeric()
                ->ignoreBlankState()
                ->rules(['nullable', 'numeric']),
        ];
    }

    public function resolveRecord(): Tenant
    {
        $houses = House::query()->where('house_name', $this->data['house_name']);

        if (filled($this->data['location_name'] ?? null)) {
            $houses->whereHas('location', fn ($q) => $q->where('location_name', $this->data['location_name']));
        }

        $house = $houses->first();

        if (! $house) {
            throw new RowImportFailedException(
                "No unit named \"{$this->data['house_name']}\" was found - import the Units sheet first."
            );
        }

        return Tenant::firstOrNew(['house_id' => $house->id]);
    }
}
