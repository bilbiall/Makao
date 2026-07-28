<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Chat extends Page
{
    protected static ?string $navigationIcon = 'heroicon-s-chat-bubble-oval-left';
    protected static ?string $navigationLabel = 'Chat';
    protected static ?int $navigationSort = 80;

    protected static string $view = 'filament.pages.chat';
    
    public function getTitle(): string
    {
        return 'Chat';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
