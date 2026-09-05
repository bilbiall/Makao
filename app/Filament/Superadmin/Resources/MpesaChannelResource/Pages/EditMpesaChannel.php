<?php

namespace App\Filament\Superadmin\Resources\MpesaChannelResource\Pages;

use App\Filament\Superadmin\Resources\MpesaChannelResource;
use App\Services\MpesaService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMpesaChannel extends EditRecord
{
    protected static string $resource = MpesaChannelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('register_c2b')
                ->label(fn () => $this->record->c2b_registered_at ? 'Re-register C2B' : 'Register C2B')
                ->color('gray')
                ->visible(fn () => (bool) $this->record->landlord?->c2b_enabled)
                ->requiresConfirmation()
                ->modalDescription('This tells Safaricom to send Paybill payment confirmations for this shortcode to Renty. Use sandbox first if you\'re not sure these credentials are correct.')
                ->action(function (MpesaService $mpesa) {
                    $result = $mpesa->registerC2bUrls($this->record);

                    if ($result['success']) {
                        $this->record->update([
                            'c2b_enabled' => true,
                            'c2b_registered_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('C2B registered with Safaricom')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Registration failed')
                            ->body($result['error'] ?? 'Unknown error')
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
