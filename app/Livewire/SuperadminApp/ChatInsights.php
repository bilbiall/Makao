<?php

namespace App\Livewire\SuperadminApp;

use App\Services\ChatInsightsService;
use Livewire\Component;

/**
 * App-shell counterpart to Filament's "Chat insights" page - same numbers (see
 * ChatInsightsService), laid out mobile-first.
 */
class ChatInsights extends Component
{
    public function render(ChatInsightsService $insights)
    {
        $daily = $insights->daily();

        return view('livewire.superadmin-app.chat-insights', [
            'stats' => $insights->stats(),
            'daily' => $daily,
            'dailyMax' => max(1, ...$daily['all']),
            'unmet' => $insights->unmetDemand(),
        ])->layout('components.layouts.app', ['title' => 'Chat insights']);
    }
}
