<?php

namespace App\Filament\Superadmin\Resources\LandlordResource\Pages;

use App\Filament\Superadmin\Resources\LandlordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandlord extends EditRecord
{
    protected static string $resource = LandlordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
