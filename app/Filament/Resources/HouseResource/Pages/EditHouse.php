<?php

namespace App\Filament\Resources\HouseResource\Pages;

use App\Filament\Resources\HouseResource;
use App\Models\HousePhoto;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHouse extends EditRecord
{
    protected static string $resource = HouseResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['new_photos'] = $this->record->photos()->pluck('path')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['listing_mode'] = !empty($data['is_short_term']) ? 'short_term' : 'long_term';
        if ($data['listing_mode'] === 'short_term') {
            $data['rent_amount'] = null;
        }
        unset($data['is_short_term']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Replace this house's photos wholesale with whatever the form submitted -
        // simplest correct behaviour for a small admin-managed list like this.
        $this->record->photos()->delete();

        foreach (($this->data['new_photos'] ?? []) as $index => $path) {
            HousePhoto::create([
                'house_id' => $this->record->id,
                'path' => $path,
                'sort_order' => $index,
            ]);
        }
    }

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
}
