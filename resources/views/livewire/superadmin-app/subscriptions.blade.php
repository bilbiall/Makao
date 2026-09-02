<div class="space-y-3">
    @forelse ($subscriptions as $sub)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $sub->landlord?->name ?? 'Unknown landlord' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $sub->package?->name }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $sub->status === 'active',
                    'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $sub->status === 'trialing',
                    'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' => in_array($sub->status, ['past_due', 'expired', 'cancelled']),
                ])>{{ ucfirst($sub->status) }}</span>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                @if ($sub->status === 'trialing' && $sub->trial_ends_at)
                    Trial ends {{ $sub->trial_ends_at->format('d M Y') }}
                @elseif ($sub->expires_at)
                    Expires {{ $sub->expires_at->format('d M Y') }}
                @endif
            </p>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No subscriptions yet.
        </div>
    @endforelse

    <div>{{ $subscriptions->links() }}</div>
</div>
