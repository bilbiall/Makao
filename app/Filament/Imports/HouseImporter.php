<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ImportsLandlordData;
use App\Models\House;
use App\Models\Location;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;

class HouseImporter extends Importer
{
    use ImportsLandlordData;

    protected static ?string $model = House::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('location_name')
                ->label('Property / building name')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                // Lookup-only - resolved to location_id in resolveRecord(), not a
                // real House column, so never write it onto the record directly.
                ->fillRecordUsing(fn () => null),

            ImportColumn::make('house_name')
                ->label('Unit name/number')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('house_type')
                ->label('Unit type (e.g. Bedsitter, 1 Bedroom, Studio)')
                ->ignoreBlankState()
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('rent_amount')
                ->label('Monthly rent (KES)')
                ->numeric()
                ->ignoreBlankState()
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('house_status')
                ->label('Status (Vacant/Occupied)')
                ->ignoreBlankState()
                ->rules(['nullable', 'in:Vacant,Occupied']),
        ];
    }

    public function resolveRecord(): House
    {
        $location = Location::firstOrCreate([
            'location_name' => $this->data['location_name'],
        ]);

        return House::firstOrNew([
            'location_id' => $location->id,
            'house_name' => $this->data['house_name'],
        ]);
    }

    protected function beforeSave(): void
    {
        // A unit already occupied by an imported tenant keeps that status even if
        // the row below (imported before tenants) hasn't set it yet - never
        // downgrade Occupied back to Vacant just because the row is silent on it.
        if (! $this->record->house_status) {
            $this->record->house_status = 'Vacant';
        }
    }
}
