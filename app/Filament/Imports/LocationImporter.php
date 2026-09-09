<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ImportsLandlordData;
use App\Models\Location;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;

/**
 * A "location" here is a building/compound (what the old system called a
 * property) - it groups the individual units imported by HouseImporter.
 */
class LocationImporter extends Importer
{
    use ImportsLandlordData;

    protected static ?string $model = Location::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('location_name')
                ->label('Property / building name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('geo_id')
                ->label('Area / neighbourhood (as it appeared in the old system)')
                ->rules(['nullable', 'max:255']),
        ];
    }

    public function resolveRecord(): Location
    {
        // locations.geo_id is NOT NULL with no default - fall back to the property
        // name itself (same as leaving "Area (free text)" blank on the manual form
        // effectively does) rather than failing the row on a blank cell.
        if (blank($this->data['geo_id'] ?? null)) {
            $this->data['geo_id'] = $this->data['location_name'];
        }

        return Location::firstOrNew([
            'location_name' => $this->data['location_name'],
        ]);
    }
}
