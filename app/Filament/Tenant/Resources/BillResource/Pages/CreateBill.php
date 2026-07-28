<?php

namespace App\Filament\Tenant\Resources\BillResource\Pages;

use App\Filament\Tenant\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;
}
