<?php

namespace App\Filament\Tenant\Resources\NoticeToVacateResource\Pages;

use App\Filament\Tenant\Resources\NoticeToVacateResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListNoticeToVacates extends ListRecords
{
    protected static string $resource = NoticeToVacateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        // Restrict list to current tenant
        $tenant = \App\Models\Tenant::where('user_id', auth()->id())->first();
        return NoticeToVacateResource::getModel()::query()->where('tenant_id', optional($tenant)->id);
    }
}
