<?php

namespace App\Filament\Superadmin\Pages;

use App\Filament\Superadmin\Widgets\ChatInsightsStats;
use App\Filament\Superadmin\Widgets\ChatTurnsChart;
use App\Filament\Superadmin\Widgets\ChatUnmetDemand;
use Filament\Pages\Page;

/**
 * How the public chat assistant is doing: volume, how often it finds homes,
 * where it hits dead ends, and what visitors wanted that we couldn't show.
 * The messages behind the dead ends are in "Chat failure inbox".
 */
class ChatInsights extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Chat assistant';

    protected static ?string $navigationLabel = 'Chat insights';

    protected static ?string $title = 'Chat insights';

    protected static ?string $slug = 'chat-insights';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.superadmin.pages.chat-insights';

    protected function getHeaderWidgets(): array
    {
        return [
            ChatInsightsStats::class,
            ChatTurnsChart::class,
            ChatUnmetDemand::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 1;
    }
}
