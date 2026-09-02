<div class="space-y-4">
    <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
        + Report an issue
    </button>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Title</label>
                <input type="text" wire:model="title" placeholder="e.g. Leaking kitchen tap" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('title') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Description</label>
                <textarea wire:model="description" rows="3" placeholder="Describe the issue in detail" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                @error('description') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="report" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Submit</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($issues as $issue)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $issue->title }}</p>
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                        'bg-rose-100 text-rose-700' => $issue->status === 'open',
                        'bg-amber-100 text-amber-700' => $issue->status === 'in_progress',
                        'bg-emerald-100 text-emerald-700' => $issue->status === 'resolved',
                    ])>{{ str_replace('_', ' ', ucfirst($issue->status)) }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $issue->description }}</p>
                <p class="mt-2 text-xs text-slate-400">Reported {{ $issue->created_at->format('d M Y') }}</p>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No issues reported yet.
            </div>
        @endforelse

        <div>{{ $issues->links() }}</div>
    </div>
</div>
