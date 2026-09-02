<div class="space-y-4">
    @if ($notices->where('status', 'pending')->isEmpty())
        <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
            + Give notice to vacate
        </button>
    @endif

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Date you plan to vacate</label>
                <input type="date" wire:model="vacate_date" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('vacate_date') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Reason</label>
                <select wire:model="reason_type" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Select a reason</option>
                    <option value="Relocation">Relocation</option>
                    <option value="Job Transfer">Job Transfer</option>
                    <option value="Rent Too High">Rent Too High</option>
                    <option value="Maintenance Issues">Maintenance Issues</option>
                    <option value="Better Offer">Found Better Offer</option>
                    <option value="Other">Other (custom)</option>
                </select>
                @error('reason_type') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            @if ($reason_type === 'Other')
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Details</label>
                    <textarea wire:model="reason_text" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                </div>
            @endif
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="submit" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Submit</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($notices as $notice)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">Vacate on {{ $notice->vacate_date->format('d M Y') }}</p>
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                        'bg-amber-100 text-amber-700' => $notice->status === 'pending',
                        'bg-emerald-100 text-emerald-700' => $notice->status === 'approved',
                        'bg-rose-100 text-rose-700' => $notice->status === 'denied',
                    ])>{{ ucfirst($notice->status) }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $notice->reason_type }}{{ $notice->reason_text ? ' - ' . $notice->reason_text : '' }}</p>
                @if ($notice->admin_notes)
                    <p class="mt-2 text-xs text-slate-500 italic">Note from landlord: {{ $notice->admin_notes }}</p>
                @endif
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No notices submitted yet.
            </div>
        @endforelse
    </div>
</div>
