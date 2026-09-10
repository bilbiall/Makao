<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Repeater's items relationship has already been saved by this point (Filament
        // saves relationships as part of record creation, before afterCreate() runs),
        // so the total is accurate here - unlike the old created() model event, which
        // fired before any items existed.
        $this->record->logAndNotifyRecorded();
    }
}
