<?php

namespace App\Filament\Resources\HouseResource\Pages;

use App\Filament\Resources\HouseResource;
use App\Models\HousePhoto;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHouse extends CreateRecord
{
    protected static string $resource = HouseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['listing_mode'] = !empty($data['is_short_term']) ? 'short_term' : 'long_term';
        if ($data['listing_mode'] === 'short_term') {
            $data['rent_amount'] = null;
        }
        unset($data['is_short_term']);

        return $data;
    }

    protected function afterCreate(): void
    {
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
}
