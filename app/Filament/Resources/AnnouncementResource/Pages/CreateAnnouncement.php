<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeCreate(): void
    {
        if (!($this->data['send_sms'] ?? false) && !($this->data['send_email'] ?? false)) {
            Notification::make()
                ->danger()
                ->title('Pick at least one channel')
                ->body('Turn on Send via SMS, Send via Email, or both.')
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['landlord_id'] = auth()->user()->landlord_id;
        $data['sent_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->send();
    }
}
