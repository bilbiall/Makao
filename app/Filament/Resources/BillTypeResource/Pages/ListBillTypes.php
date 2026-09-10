<?php

namespace App\Filament\Resources\BillTypeResource\Pages;

use App\Filament\Resources\BillTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBillTypes extends ListRecords
{
    protected static string $resource = BillTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
