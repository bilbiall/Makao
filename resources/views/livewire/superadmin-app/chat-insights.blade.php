@php
    $pct = fn (int $part, int $whole) => \App\Services\ChatInsightsService::percent($part, $whole);
    $card = 'rounded-xl bg-white border border-slate-200 p-3 dark:bg-slate-900 dark:border-slate-800';
    $tiles = [
        ['Conversations', number_format($stats['conversations']), number_format($stats['messages']).' messages', 'text-slate-900 dark:text-slate-100'],
        ['Searches that found homes', $pct($stats['found'], $stats['searches']), "{$stats['found']} of {$stats['searches']} searches", ($stats['searches'] && $stats['found'] / $stats['searches'] < 0.5) ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400'],
        ['Dead ends', number_format($stats['dead_ends']), 'No match, or it had to ask again', $stats['dead_ends'] ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400'],
        ['Offers accepted', $pct($stats['offers_accepted'], $stats['offers_total']), "{$stats['offers_accepted']} of {$stats['offers_total']} answered", 'text-slate-900 dark:text-slate-100'],
        ['Swahili messages', $pct($stats['swahili'], $stats['messages']), number_format($stats['swahili']).' messages', 'text-slate-900 dark:text-slate-100'],
        ['Sent to the team', number_format($stats['handoffs']), 'WhatsApp / call / email shown', 'text-slate-900 dark:text-slate-100'],
        ['AI call failed', $pct($stats['llm_failed'], $stats['messages']), 'Fell back to keywords', ($stats['messages'] && $stats['llm_failed'] / $stats['messages'] > 0.2) ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100'],
    ];
@endphp
<div class="space-y-5">
    <div class="flex gap-2">
        <a href="{{ route('app.superadmin.chat-inbox') }}" class="flex-1 rounded-xl bg-emerald-600 py-3 text-center text-sm font-semibold text-white hover:bg-emerald-700">Failure inbox</a>
        <a href="{{ route('app.superadmin.chat-phrases') }}" class="flex-1 rounded-xl border border-slate-300 bg-white py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">Taught phrases</a>
    </div>

    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Last 30 days</p>

    <div class="grid grid-cols-2 gap-2">
        @foreach ($tiles as [$label, $value, $hint, $color])
            <div class="{{ $card }}">
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold {{ $color }}">{{ $value }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    {{-- Messages per day: plain CSS bars (amber = dead ends) - no chart library needed. --}}
    <div class="{{ $card }}">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Messages per day</p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mb-3"><span class="inline-block h-2 w-2 rounded-sm bg-emerald-500"></span> all messages &nbsp; <span class="inline-block h-2 w-2 rounded-sm bg-amber-500"></span> dead ends</p>
        <div class="flex h-24 items-end gap-px" role="img" aria-label="Messages per day over the last 30 days">
            @foreach ($daily['all'] as $i => $count)
                <div class="relative flex h-full flex-1 items-end" title="{{ $daily['labels'][$i] }}: {{ $count }} messages, {{ $daily['dead'][$i] }} dead ends">
                    <div class="w-full rounded-t-sm bg-emerald-500/80" style="height: {{ round($count / $dailyMax * 100) }}%"></div>
                    @if ($daily['dead'][$i] > 0)
                        <div class="absolute bottom-0 w-full rounded-t-sm bg-amber-500" style="height: {{ round($daily['dead'][$i] / $dailyMax * 100) }}%"></div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="mt-1 flex justify-between text-[10px] text-slate-400 dark:text-slate-500">
            <span>{{ $daily['labels'][0] }}</span>
            <span>{{ end($daily['labels']) }}</span>
        </div>
    </div>

    <div class="{{ $card }}">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Unmet demand</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Searches where the assistant had no homes to show. Stock added here meets real demand.</p>

        @forelse ($unmet as $row)
            <div class="flex items-center justify-between gap-3 border-t border-slate-100 py-2 first:border-t-0 dark:border-slate-800">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $row['type'] }}</p>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $row['place'] }}{{ $row['typical_budget'] ? ' · around KES '.number_format($row['typical_budget']) : '' }}</p>
                </div>
                <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">{{ $row['searches'] }} {{ \Illuminate\Support\Str::plural('search', $row['searches']) }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500 dark:text-slate-400">No unmet searches yet.</p>
        @endforelse
    </div>
</div>
