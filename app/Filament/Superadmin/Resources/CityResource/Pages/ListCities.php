<?php

namespace App\Filament\Superadmin\Resources\CityResource\Pages;

use App\Filament\Superadmin\Resources\CityResource;
use App\Models\City;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCities extends ListRecords
{
    protected static string $resource = CityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /** The coverage report at a glance - how many of the 47/48 towns actually have real neighbourhood data behind them yet. */
    public function getSubheading(): ?string
    {
        $total = City::count();
        $open = City::where('is_open', true)->count();
        $configured = City::has('areas')->count();

        return "{$open} open to landlords, {$configured} of {$total} have neighbourhoods set up.";
    }
}
