<div class="space-y-3">
    @forelse ($landlords as $landlord)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $landlord->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $landlord->contact_email }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $landlord->status === 'active',
                    'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' => $landlord->status === 'suspended',
                ])>{{ ucfirst($landlord->status) }}</span>
            </div>
            @if ($landlord->currentSubscription)
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    {{ $landlord->currentSubscription->package?->name }} &middot;
                    <span class="capitalize">{{ $landlord->currentSubscription->status }}</span>
                </p>
            @endif
            <a href="{{ url('/superadmin/landlords/' . $landlord->id . '/settings') }}" class="mt-3 block text-center rounded-lg border border-slate-300 py-2 text-xs font-medium text-slate-700 dark:border-slate-700 dark:text-slate-300">
                Manage settings
            </a>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No landlords yet.
        </div>
    @endforelse

    <div>{{ $landlords->links() }}</div>
</div>
