<?php

namespace App\Filament\Superadmin\Widgets;

use App\Services\ChatInsightsService;
use Filament\Widgets\ChartWidget;

class ChatTurnsChart extends ChartWidget
{
    protected static ?string $heading = 'Messages per day (last 30 days)';

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $daily = app(ChatInsightsService::class)->daily();

        return [
            'datasets' => [
                ['label' => 'All messages', 'data' => $daily['all']],
                ['label' => 'Dead ends', 'data' => $daily['dead'], 'borderColor' => '#f59e0b'],
            ],
            'labels' => $daily['labels'],
        ];
    }
}
