<?php

namespace App\Filament\Superadmin\Resources\SubscriptionResource\Pages;

use App\Filament\Superadmin\Resources\SubscriptionResource;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['recorded_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
