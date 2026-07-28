<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Tenant')
                ->modalDescription(function ($record) {
                    $house = $record->house->house_name ?? 'N/A';
                    $balance = $record->latestPayment->balance ?? 0;
                    
                    $balanceText = $balance == 0 
                        ? '<span style="color: green;">No outstanding balance</span>' 
                        : ($balance > 0 
                            ? '<span style="color: red;">Outstanding balance: KES ' . number_format($balance, 2) . '</span>'
                            : '<span style="color: orange;">Overpayment: KES ' . number_format(abs($balance), 2) . '</span>');
                    
                    return new HtmlString(
                        '<div style="margin-top: 10px;">' .
                        '<p><strong>Tenant:</strong> ' . $record->tenant_name . '</p>' .
                        '<p><strong>House:</strong> ' . $house . '</p>' .
                        '<p><strong>Financial Status:</strong> ' . $balanceText . '</p>' .
                        '<p style="margin-top: 15px; color: #666;">This tenant\'s data will be archived for 60 days before permanent deletion. All invoices, payments, and issues will be preserved in the archive.</p>' .
                        '</div>'
                    );
                })
                ->modalSubmitActionLabel('Yes, Delete Tenant')
                ->successNotificationTitle('Tenant Deleted')
                ->successRedirectUrl(TenantResource::getUrl('index')),
        ];
    }
}
