<?php

namespace App\Filament\Superadmin\Resources\MpesaChannelResource\Pages;

use App\Filament\Superadmin\Resources\MpesaChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMpesaChannels extends ListRecords
{
    protected static string $resource = MpesaChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(fn () => static::getResource()::getUrl('create', request()->has('landlord_id') ? ['landlord_id' => request()->integer('landlord_id')] : [])),
        ];
    }
}
