<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // The payment's own `balance` snapshot column, and the parent invoice/
        // tenant balances, are recomputed authoritatively by
        // Payment::booted()'s `updated` listener (Invoice::recalculateBalance())
        // once amount_paid actually changes - don't set it here.
        unset($data['balance']);

        return $data;
    }
}
