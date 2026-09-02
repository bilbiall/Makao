<div class="space-y-3">
    @forelse ($requests as $request)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between dark:bg-slate-900 dark:border-slate-800">
            <div>
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $request->house?->house_name ?? 'House removed' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $request->house?->location?->location_name }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Requested {{ $request->requested_at?->format('d M Y') }}</p>
                @if ($request->admin_notes)
                    <p class="text-xs text-slate-500 mt-1 italic">"{{ $request->admin_notes }}"</p>
                @endif
            </div>
            <span @class([
                'rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                'bg-amber-100 text-amber-700' => $request->status === 'pending',
                'bg-emerald-100 text-emerald-700' => $request->status === 'admitted',
                'bg-slate-100 text-slate-600' => $request->status === 'revoked',
            ])>{{ ucfirst($request->status) }}</span>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No viewing requests yet. <a href="{{ route('listings.index') }}" class="text-emerald-700 font-medium">Browse listings →</a>
        </div>
    @endforelse

    {{ $requests->links() }}
</div>
