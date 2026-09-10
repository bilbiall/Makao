<div class="space-y-3">
    @if (session('issue-deleted'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-3 py-2 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-300">
            {{ session('issue-deleted') }}
        </div>
    @endif

    <div class="flex gap-2">
        <select wire:model.live="statusFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <option value="">All statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In progress</option>
            <option value="resolved">Resolved</option>
        </select>
        <select wire:model.live="locationFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <option value="">All locations</option>
            @foreach ($locationOptions as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="export" class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
            @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
        </button>
    </div>

    @if (!empty($selected))
        <div class="flex items-center justify-between rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 dark:bg-rose-500/10 dark:border-rose-500/20">
            <span class="text-xs font-medium text-rose-700 dark:text-rose-400">{{ count($selected) }} selected</span>
            <button type="button" wire:click="deleteSelected" wire:confirm="Delete the selected issues? This can't be undone." class="text-xs font-semibold text-rose-700 dark:text-rose-400">
                Delete selected
            </button>
        </div>
    @endif

    @forelse ($issues as $issue)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-2">
                    <input type="checkbox" wire:model.live="selected" value="{{ $issue->id }}" class="mt-1 rounded border-slate-300 dark:border-slate-700">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $issue->title }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $issue->tenant?->tenant_name ?? 'Unknown tenant' }} &middot; {{ $issue->tenant?->house?->location?->location_name }}</p>
                    </div>
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
            <div class="mt-2">
                <button wire:click="delete({{ $issue->id }})" wire:confirm="Delete this issue? This can't be undone." class="w-full rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                    Delete
                </button>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No issues found.
        </div>
    @endforelse

    <div>{{ $issues->links() }}</div>
</div>
