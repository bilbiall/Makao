<?php

namespace App\Filament\Resources\MpesaChannelResource\Pages;

use App\Filament\Resources\MpesaChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMpesaChannel extends CreateRecord
{
    protected static string $resource = MpesaChannelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
