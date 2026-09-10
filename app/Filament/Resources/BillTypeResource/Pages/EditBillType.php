<?php

namespace App\Filament\Resources\BillTypeResource\Pages;

use App\Filament\Resources\BillTypeResource;
use App\Models\BillType;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillType extends EditRecord
{
    protected static string $resource = BillTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (BillType $record, Actions\DeleteAction $action) {
                    if ($record->items()->exists()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('This type has bills recorded against it')
                            ->body('Deactivate it instead of deleting, so past bills keep their breakdown.')
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
