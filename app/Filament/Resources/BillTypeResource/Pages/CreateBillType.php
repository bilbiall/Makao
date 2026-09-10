<?php

namespace App\Filament\Resources\BillTypeResource\Pages;

use App\Filament\Resources\BillTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillType extends CreateRecord
{
    protected static string $resource = BillTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['landlord_id'] = auth()->user()->landlord_id;

        return $data;
    }
}
