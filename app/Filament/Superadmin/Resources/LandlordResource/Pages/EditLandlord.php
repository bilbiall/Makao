<?php

namespace App\Filament\Superadmin\Resources\LandlordResource\Pages;

use App\Filament\Superadmin\Resources\LandlordResource;
use Filament\Actions;
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
