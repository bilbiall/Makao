<div class="space-y-3">
    @foreach ($turns as $turn)
        <div @class(['rounded-lg p-3 text-sm space-y-1', 'ring-2 ring-primary-500' => $turn->id === $highlight, 'bg-gray-50 dark:bg-white/5'])>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $turn->created_at->format('j M, H:i') }} &middot; {{ $turn->branch ?? '-' }}{{ $turn->handoff_shown ? ' · sent to team' : '' }}</p>
            <p><span class="font-semibold">Visitor:</span> {{ $turn->user_text }}</p>
            <p class="whitespace-pre-line"><span class="font-semibold">Assistant:</span> {{ $turn->reply_text }}</p>
            @if (! empty($turn->filters))
                <p class="text-xs text-gray-500 dark:text-gray-400">Understood as: {{ collect($turn->filters)->map(fn ($v, $k) => $k.': '.(is_array($v) ? implode(', ', $v) : $v))->implode(' · ') }}</p>
            @endif
        </div>
    @endforeach
</div>
