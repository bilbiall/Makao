<?php

namespace App\Filament\Superadmin\Widgets;

use App\Services\ChatInsightsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ChatInsightsStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return 'Last 30 days';
    }

    protected function getStats(): array
    {
        $s = app(ChatInsightsService::class)->stats();
        $pct = fn (int $part, int $whole) => ChatInsightsService::percent($part, $whole);

        return [
            Stat::make('Conversations', number_format($s['conversations']))
                ->description(number_format($s['messages']).' messages'),

            Stat::make('Searches that found homes', $pct($s['found'], $s['searches']))
                ->description("{$s['found']} of {$s['searches']} searches")
                ->color($s['searches'] && $s['found'] / $s['searches'] < 0.5 ? 'danger' : 'success'),

            Stat::make('Dead ends', number_format($s['dead_ends']))
                ->description('No match, or the bot had to ask again')
                ->color($s['dead_ends'] ? 'warning' : 'success'),

            Stat::make('Offers accepted', $pct($s['offers_accepted'], $s['offers_total']))
                ->description("{$s['offers_accepted']} of {$s['offers_total']} answered offers"),

            Stat::make('Swahili messages', $pct($s['swahili'], $s['messages']))
                ->description(number_format($s['swahili']).' messages'),

            Stat::make('Sent to the team', number_format($s['handoffs']))
                ->description('WhatsApp / call / email shown'),

            Stat::make('AI call failed', $pct($s['llm_failed'], $s['messages']))
                ->description('Fell back to keyword matching')
                ->color($s['messages'] && $s['llm_failed'] / $s['messages'] > 0.2 ? 'danger' : 'gray'),
        ];
    }
}
