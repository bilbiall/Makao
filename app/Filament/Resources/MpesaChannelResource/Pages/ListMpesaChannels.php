<?php

namespace App\Filament\Resources\MpesaChannelResource\Pages;

use App\Filament\Resources\MpesaChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMpesaChannels extends ListRecords
{
    protected static string $resource = MpesaChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
