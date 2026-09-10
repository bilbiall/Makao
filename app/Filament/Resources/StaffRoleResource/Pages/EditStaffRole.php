<?php

namespace App\Filament\Resources\StaffRoleResource\Pages;

use App\Filament\Resources\StaffRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaffRole extends EditRecord
{
    protected static string $resource = StaffRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (\App\Models\StaffRole $record, Actions\DeleteAction $action) {
                    if ($record->users()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Reassign staff before deleting this role')
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
