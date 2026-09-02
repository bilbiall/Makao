<div class="space-y-5">
    <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Welcome back,</p>
        <p class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
    </div>

    <a href="{{ route('listings.index') }}" class="block rounded-2xl bg-emerald-600 text-white p-4 text-center font-semibold shadow-sm hover:bg-emerald-700 transition">
        Find a house →
    </a>

    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('app.user.watchlist') }}" class="rounded-2xl bg-rose-50 border border-rose-100 p-4">
            <p class="text-xs text-rose-700">Watchlist</p>
            <p class="mt-1 text-xl font-bold text-rose-800">{{ $watchlistCount }}</p>
        </a>
        <a href="{{ route('app.user.applications') }}" class="rounded-2xl bg-amber-50 border border-amber-100 p-4">
            <p class="text-xs text-amber-700 dark:text-amber-400">Pending requests</p>
            <p class="mt-1 text-xl font-bold text-amber-800">{{ $pendingRequestsCount }}</p>
        </a>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recent viewing requests</p>
            <a href="{{ route('app.user.applications') }}" class="text-xs font-medium text-emerald-700 dark:text-emerald-400">See all</a>
        </div>
        @forelse ($recentRequests as $request)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 dark:border-slate-800/60 last:border-0">
                <div>
                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ $request->house?->house_name ?? 'House removed' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $request->requested_at?->format('d M Y') }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-amber-100 text-amber-700' => $request->status === 'pending',
                    'bg-emerald-100 text-emerald-700' => $request->status === 'admitted',
                    'bg-slate-100 text-slate-600' => $request->status === 'revoked',
                ])>{{ ucfirst($request->status) }}</span>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400 text-center">No viewing requests yet.</p>
        @endforelse
    </div>
</div>
