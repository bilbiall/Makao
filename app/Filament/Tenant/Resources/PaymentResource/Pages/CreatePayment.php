<?php

namespace App\Filament\Tenant\Resources\PaymentResource\Pages;

use App\Filament\Tenant\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentBalance = isset($data['balance']) ? (float) str_replace(',', '', $data['balance']) : 0;
        $amountPaid = isset($data['amount_paid']) ? (float) $data['amount_paid'] : 0;
        $data['balance'] = $currentBalance - $amountPaid;
        return $data;
    }
}
