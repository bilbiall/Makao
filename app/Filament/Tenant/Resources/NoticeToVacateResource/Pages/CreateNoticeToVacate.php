<?php

namespace App\Filament\Tenant\Resources\NoticeToVacateResource\Pages;

use App\Filament\Tenant\Resources\NoticeToVacateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNoticeToVacate extends CreateRecord
{
    protected static string $resource = NoticeToVacateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = \App\Models\Tenant::where('user_id', auth()->id())->firstOrFail();
        $data['tenant_id'] = $tenant->id;
        $data['status'] = 'pending';
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
