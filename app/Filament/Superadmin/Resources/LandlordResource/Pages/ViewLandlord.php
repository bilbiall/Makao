<?php

namespace App\Filament\Superadmin\Resources\LandlordResource\Pages;

use App\Filament\Superadmin\Resources\LandlordResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLandlord extends ViewRecord
{
    protected static string $resource = LandlordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Account')
                    ->schema([
                        Components\TextEntry::make('name')->label('Business Name'),
                        Components\TextEntry::make('contact_email'),
                        Components\TextEntry::make('phone_number'),
                        Components\TextEntry::make('status')->badge(),
                        Components\TextEntry::make('created_at')->label('Joined')->dateTime(),
                    ])->columns(2),

                Components\Section::make('Usage')
                    ->schema([
                        Components\TextEntry::make('locations_count')->label('Locations')->state(fn ($record) => $record->locations()->count()),
                        Components\TextEntry::make('houses_count')->label('Houses/Units')->state(fn ($record) => $record->houses()->count()),
                        Components\TextEntry::make('tenants_count')->label('Tenants')->state(fn ($record) => $record->tenants()->count()),
                    ])->columns(3),
            ]);
    }
}
