<div class="space-y-4">
    @if ($notices->where('status', 'pending')->isEmpty())
        <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
            + Give notice to vacate
        </button>
    @endif

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3">
            <div>
                <label class="text-xs font-medium text-slate-600">Date you plan to vacate</label>
                <input type="date" wire:model="vacate_date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('vacate_date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Reason</label>
                <select wire:model="reason_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select a reason</option>
                    <option value="Relocation">Relocation</option>
                    <option value="Job Transfer">Job Transfer</option>
                    <option value="Rent Too High">Rent Too High</option>
                    <option value="Maintenance Issues">Maintenance Issues</option>
                    <option value="Better Offer">Found Better Offer</option>
                    <option value="Other">Other (custom)</option>
                </select>
                @error('reason_type') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            @if ($reason_type === 'Other')
                <div>
                    <label class="text-xs font-medium text-slate-600">Details</label>
                    <textarea wire:model="reason_text" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>
            @endif
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-medium text-slate-700">Cancel</button>
                <button wire:click="submit" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Submit</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($notices as $notice)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
                <div class="flex items-start justify-between">
                    <p class="font-semibold text-slate-900">Vacate on {{ $notice->vacate_date->format('d M Y') }}</p>
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                        'bg-amber-100 text-amber-700' => $notice->status === 'pending',
                        'bg-emerald-100 text-emerald-700' => $notice->status === 'approved',
                        'bg-rose-100 text-rose-700' => $notice->status === 'denied',
                    ])>{{ ucfirst($notice->status) }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $notice->reason_type }}{{ $notice->reason_text ? ' - ' . $notice->reason_text : '' }}</p>
                @if ($notice->admin_notes)
                    <p class="mt-2 text-xs text-slate-500 italic">Note from landlord: {{ $notice->admin_notes }}</p>
                @endif
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
                No notices submitted yet.
            </div>
        @endforelse
    </div>
</div>
