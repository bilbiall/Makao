<div class="space-y-3">
    @forelse ($issues as $issue)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $issue->title }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $issue->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-rose-100 text-rose-700' => $issue->status === 'open',
                    'bg-amber-100 text-amber-700' => $issue->status === 'in_progress',
                    'bg-emerald-100 text-emerald-700' => $issue->status === 'resolved',
                ])>{{ str_replace('_', ' ', ucfirst($issue->status)) }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $issue->description }}</p>
            <div class="mt-3 flex gap-2">
                <button wire:click="updateStatus({{ $issue->id }}, 'open')" class="flex-1 rounded-lg border border-rose-200 text-rose-700 text-xs font-medium py-2 {{ $issue->status === 'open' ? 'bg-rose-50' : '' }}">Open</button>
                <button wire:click="updateStatus({{ $issue->id }}, 'in_progress')" class="flex-1 rounded-lg border border-amber-200 text-amber-700 text-xs font-medium py-2 {{ $issue->status === 'in_progress' ? 'bg-amber-50' : '' }}">In progress</button>
                <button wire:click="updateStatus({{ $issue->id }}, 'resolved')" class="flex-1 rounded-lg border border-emerald-200 text-emerald-700 text-xs font-medium py-2 {{ $issue->status === 'resolved' ? 'bg-emerald-50' : '' }}">Resolved</button>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No issues reported yet.
        </div>
    @endforelse

    <div>{{ $issues->links() }}</div>
</div>
