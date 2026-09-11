<div class="space-y-3">
    <p class="text-sm text-slate-500 dark:text-slate-400">House seekers who've watchlisted, requested a viewing, or set an alert matching one of your properties.</p>

    @forelse ($rows as $row)
        @php $user = $row['user']; @endphp
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}{{ $user->phone_number ? ' · ' . $user->phone_number : '' }}</p>
                </div>
            </div>

            <div class="mt-3 space-y-1.5">
                @foreach ($row['reasons'] as $reason)
                    <div class="flex items-center gap-2 text-xs">
                        @if ($reason['type'] === 'watchlist')
                            <span class="rounded-full bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 px-2 py-0.5 font-medium flex-shrink-0">Watchlisted</span>
                            <span class="text-slate-600 dark:text-slate-400">{{ $reason['house']?->publicName() ?? 'Listing removed' }}</span>
                        @elseif ($reason['type'] === 'viewing_request')
                            <span class="rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 px-2 py-0.5 font-medium flex-shrink-0">Requested viewing ({{ $reason['status'] }})</span>
                            <span class="text-slate-600 dark:text-slate-400">{{ $reason['house']?->publicName() ?? 'Listing removed' }}</span>
                        @elseif ($reason['type'] === 'alert_specific')
                            <span class="rounded-full bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400 px-2 py-0.5 font-medium flex-shrink-0">Alert set</span>
                            <span class="text-slate-600 dark:text-slate-400">Notify when "{{ $reason['house']?->publicName() ?? 'a removed listing' }}" is available</span>
                        @else
                            <span class="rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 px-2 py-0.5 font-medium flex-shrink-0">Alert matches</span>
                            <span class="text-slate-600 dark:text-slate-400">{{ $reason['alert']->describe() }} - matches {{ $reason['houses']->count() }} of your {{ $reason['houses']->count() === 1 ? 'listings' : 'listings' }} ({{ $reason['houses']->map->publicName()->join(', ') }})</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No house seekers have shown interest in your properties yet.
        </div>
    @endforelse
</div>
