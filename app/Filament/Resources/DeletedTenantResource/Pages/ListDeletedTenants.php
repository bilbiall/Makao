<?php

namespace App\Filament\Resources\DeletedTenantResource\Pages;

use App\Filament\Resources\DeletedTenantResource;
use Filament\Resources\Pages\ListRecords;

class ListDeletedTenants extends ListRecords
{
    protected static string $resource = DeletedTenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions can be added here (e.g., restore, export)
        ];
    }
}
