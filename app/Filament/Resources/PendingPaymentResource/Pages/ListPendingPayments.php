<?php

namespace App\Filament\Resources\PendingPaymentResource\Pages;

use App\Filament\Resources\PendingPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListPendingPayments extends ListRecords
{
    protected static string $resource = PendingPaymentResource::class;
}
