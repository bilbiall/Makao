<?php

namespace App\Filament\Superadmin\Resources\ChatAliasResource\Pages;

use App\Filament\Superadmin\Resources\ChatAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChatAliases extends ListRecords
{
    protected static string $resource = ChatAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
