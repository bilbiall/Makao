<?php

namespace App\Filament\Superadmin\Resources\MpesaChannelResource\Pages;

use App\Filament\Superadmin\Resources\MpesaChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMpesaChannel extends CreateRecord
{
    protected static string $resource = MpesaChannelResource::class;

    /** Lets the "M-Pesa Channels" link on a landlord's row arrive here pre-filled. */
    public function mount(): void
    {
        parent::mount();

        if ($landlordId = request()->integer('landlord_id')) {
            $this->form->fill(array_replace($this->form->getState(), ['landlord_id' => $landlordId]));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
