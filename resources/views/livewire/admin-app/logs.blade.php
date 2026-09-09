@php
    $fieldClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
@endphp
<div class="space-y-4">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">User</label>
                <select wire:model.live="log_user" class="mt-1 {{ $fieldClass }}">
                    <option value="">Anyone</option>
                    @foreach ($usersList as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Activity type</label>
                <select wire:model.live="log_action" class="mt-1 {{ $fieldClass }}">
                    <option value="">Any activity</option>
                    @foreach ($actionsList as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">From</label>
                <input type="date" wire:model.live="log_from" class="mt-1 {{ $fieldClass }}">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">To</label>
                <input type="date" wire:model.live="log_to" class="mt-1 {{ $fieldClass }}">
            </div>
        </div>
        @if ($log_user || $log_action || $log_from || $log_to)
            <button wire:click="clearFilters" class="text-xs font-medium text-emerald-700 hover:underline dark:text-emerald-400">
                Clear filters
            </button>
        @endif
    </div>

    <div class="space-y-2">
        @forelse ($logs as $log)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-900 dark:text-slate-100">{{ $log->details ?: ucfirst(str_replace('_', ' ', $log->action)) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at->format('d M Y, H:i') }}
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No activity matches these filters.
            </div>
        @endforelse
    </div>

    <div>{{ $logs->links() }}</div>
</div>
