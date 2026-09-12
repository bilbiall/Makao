<div class="space-y-3">
    @forelse ($inquiries as $inquiry)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $inquiry->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $inquiry->phone }}{{ $inquiry->email ? ' · ' . $inquiry->email : '' }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $inquiry->house?->publicName() ?? 'Listing removed' }} &middot; {{ $inquiry->created_at->diffForHumans() }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $inquiry->status === 'new',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $inquiry->status === 'responded',
                ])>{{ ucfirst($inquiry->status) }}</span>
            </div>

            @if ($inquiry->message)
                <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $inquiry->message }}</p>
            @endif

            <div class="mt-3 flex gap-2">
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $inquiry->phone) }}" target="_blank" rel="noopener" class="flex-1 text-center rounded-lg bg-[#25D366] py-2 text-xs font-semibold text-white hover:opacity-90">
                    Reply on WhatsApp
                </a>
                @if ($inquiry->status !== 'responded')
                    <button type="button" wire:click="markResponded({{ $inquiry->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                        Mark responded
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No questions from guests yet.
        </div>
    @endforelse

    {{ $inquiries->links() }}
</div>
