<?php

namespace App\Filament\Resources\MpesaTransactionResource\Pages;

use App\Filament\Resources\MpesaTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListMpesaTransactions extends ListRecords
{
    protected static string $resource = MpesaTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for transactions (they're created via payment flow)
        ];
    }
}
