<?php

namespace App\Filament\Resources\StaffRoleResource\Pages;

use App\Filament\Resources\StaffRoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaffRole extends CreateRecord
{
    protected static string $resource = StaffRoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['landlord_id'] = auth()->user()->landlord_id;

        return $data;
    }
}
