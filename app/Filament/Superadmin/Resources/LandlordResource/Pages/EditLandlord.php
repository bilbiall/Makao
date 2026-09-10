<?php

namespace App\Filament\Superadmin\Resources\LandlordResource\Pages;

use App\Filament\Superadmin\Resources\LandlordResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditLandlord extends EditRecord
{
    protected static string $resource = LandlordResource::class;

    protected ?string $ownerName = null;
    protected ?string $ownerEmail = null;
    protected ?string $ownerPassword = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $owner = $this->record->owner;

        $data['owner_name'] = $owner?->name;
        $data['owner_email'] = $owner?->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->ownerName = $data['owner_name'] ?? null;
        $this->ownerEmail = $data['owner_email'] ?? null;
        $this->ownerPassword = $data['owner_password'] ?? null;

        unset($data['owner_name'], $data['owner_email'], $data['owner_password']);

        return $data;
    }

    protected function afterSave(): void
    {
        $owner = $this->record->owner;

        if (! $owner) {
            // No linked role='landlord' User to update - surface this loudly
            // rather than silently discarding the owner_name/owner_email/
            // owner_password fields the superadmin just typed and saved.
            if ($this->ownerName || $this->ownerEmail || $this->ownerPassword) {
                Notification::make()
                    ->danger()
                    ->title('No owner account found for this business')
                    ->body('The business details were saved, but there is no linked login account (role=landlord User) to apply the name/email/password change to.')
                    ->persistent()
                    ->send();
            }

            return;
        }

        $ownerData = array_filter([
            'name' => $this->ownerName,
            'email' => $this->ownerEmail,
        ], fn ($value) => filled($value));

        if ($this->ownerPassword) {
            $ownerData['password'] = Hash::make($this->ownerPassword);
        }

        if (! empty($ownerData)) {
            $owner->update($ownerData);
        }
    }
}
