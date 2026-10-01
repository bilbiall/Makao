<?php

namespace App\Filament\Superadmin\Widgets;

use App\Services\ChatInsightsService;
use Filament\Widgets\Widget;

/**
 * What visitors searched for that we had nothing to show them - the most
 * useful output of the chat logs for landlords and for the pitch.
 */
class ChatUnmetDemand extends Widget
{
    protected static string $view = 'filament.superadmin.widgets.chat-unmet-demand';

    protected int|string|array $columnSpan = 'full';

    public function getRows(): array
    {
        return app(ChatInsightsService::class)->unmetDemand();
    }
}
