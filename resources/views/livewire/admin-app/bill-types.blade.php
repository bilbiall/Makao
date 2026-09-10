@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-4">
    @if (session('bill-type-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('bill-type-saved') }}
        </div>
    @endif
    @if (session('bill-type-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('bill-type-error') }}
        </div>
    @endif

    @if (!$showForm)
        <button type="button" wire:click="startCreate" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
            + New bill type
        </button>
    @else
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-4 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit bill type' : 'New bill type' }}</p>

            <div>
                <label class="{{ $labelClass }}">Name</label>
                <input type="text" wire:model="name" placeholder="e.g. Water, Trash, Security" class="{{ $inputClass }}">
                @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Applies to</label>
                <select wire:model="location_id" class="{{ $inputClass }}">
                    <option value="">All my properties</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $labelClass }}">Default amount (KES)</label>
                <input type="number" wire:model="default_amount" placeholder="Optional" class="{{ $inputClass }}">
                <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Prefills this amount on a manual bill, and is the exact amount used every month if marked recurring.</p>
                @error('default_amount') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-start gap-2 rounded-lg border border-slate-200 dark:border-slate-700 p-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" wire:model="is_recurring" class="mt-0.5 rounded border-slate-300 dark:border-slate-700">
                <span>
                    <span class="font-medium block">Recurring - auto-bill every month</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Automatically added to every tenant in the propert{{ $location_id ? 'y' : 'ies' }} above, every month, alongside rent.</span>
                </span>
            </label>

            <label class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
                <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 dark:border-slate-700">
                Active (uncheck to stop offering this on new bills without deleting its history)
            </label>

            <div class="flex gap-3">
                <button type="button" wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button type="button" wire:click="save" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($types as $type)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $type->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $type->location?->location_name ?? 'All properties' }}
                            @if ($type->default_amount !== null)
                                &middot; KES {{ number_format($type->default_amount) }}
                            @endif
                            &middot; {{ $type->items_count }} bill{{ $type->items_count === 1 ? '' : 's' }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        @if ($type->is_recurring)
                            <span class="rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 px-2.5 py-0.5 text-[11px] font-medium">Recurring</span>
                        @endif
                        @unless ($type->is_active)
                            <span class="rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 px-2.5 py-0.5 text-[11px] font-medium">Inactive</span>
                        @endunless
                    </div>
                </div>
                <div class="mt-3 flex gap-2">
                    <button type="button" wire:click="startEdit({{ $type->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                        Edit
                    </button>
                    <button type="button" wire:click="delete({{ $type->id }})" wire:confirm="Delete this bill type?" class="flex-1 rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                        Delete
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No bill types yet - add the charges you usually bill (e.g. Water, Trash), then use them on the Bills page.
            </div>
        @endforelse
    </div>
</div>
