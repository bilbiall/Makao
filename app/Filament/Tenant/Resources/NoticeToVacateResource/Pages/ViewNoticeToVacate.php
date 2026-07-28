<?php

namespace App\Filament\Tenant\Resources\NoticeToVacateResource\Pages;

use App\Filament\Tenant\Resources\NoticeToVacateResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewNoticeToVacate extends ViewRecord
{
    protected static string $resource = NoticeToVacateResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('vacate_date')->label('Vacate Date')->date(),
            Infolists\Components\TextEntry::make('reason_type')->label('Reason')->badge(),
            Infolists\Components\TextEntry::make('reason_text')->label('Details')->visible(fn ($record) => !empty($record->reason_text)),
            Infolists\Components\TextEntry::make('status')->badge()->color(fn (string $state) => match ($state) {
                'approved' => 'success',
                'denied' => 'danger',
                default => 'warning',
            }),
            Infolists\Components\TextEntry::make('approved_at')->label('Approved On')->dateTime()->visible(fn ($record) => !empty($record->approved_at)),
        ]);
    }
}
