<?php

namespace App\Filament\Superadmin\Resources\ChatAliasResource\Pages;

use App\Filament\Superadmin\Resources\ChatAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChatAlias extends EditRecord
{
    protected static string $resource = ChatAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
