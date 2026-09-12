@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-3">
    @if (session('promo-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('promo-saved') }}
        </div>
    @endif

    <p class="text-sm text-slate-500 dark:text-slate-400">Run a time-boxed discount on any price package - it shows as a strikethrough price and a badge on the listing, and applies automatically to any booking made while it's active.</p>

    @forelse ($houses as $house)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $house->publicName() }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $house->location?->location_name }}</p>

            <div class="mt-3 space-y-2">
                @forelse ($house->pricePackages as $package)
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $package->name }} - KES {{ number_format($package->price) }}/{{ $package->billing_unit }}</p>
                                @if ($package->discount_percent)
                                    <p class="text-xs {{ $package->hasActiveDiscount() ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                        {{ $package->discount_percent }}% off &rarr; KES {{ number_format($package->discountedPrice()) }}
                                        {{ $package->hasActiveDiscount() ? '(active)' : '(not active yet / expired)' }}
                                        @if ($package->discount_label) &middot; {{ $package->discount_label }} @endif
                                    </p>
                                @endif
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <button type="button" wire:click="startEdit({{ $package->id }})" class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300">
                                    {{ $package->discount_percent ? 'Edit' : 'Add discount' }}
                                </button>
                                @if ($package->discount_percent)
                                    <button type="button" wire:click="clear({{ $package->id }})" wire:confirm="Remove this discount?" class="rounded-lg border border-rose-200 dark:border-rose-500/30 px-3 py-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">
                                        Remove
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($editingPackageId === $package->id)
                            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="{{ $labelClass }}">Discount %</label>
                                        <input type="number" min="1" max="90" wire:model="discount_percent" class="{{ $inputClass }}">
                                        @error('discount_percent') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}">Label (optional)</label>
                                        <input type="text" wire:model="discount_label" placeholder="e.g. Weekend special" class="{{ $inputClass }}">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="{{ $labelClass }}">Starts (optional)</label>
                                        <input type="datetime-local" wire:model="discount_starts_at" class="{{ $inputClass }}">
                                    </div>
                                    <div>
                                        <label class="{{ $labelClass }}">Ends (optional)</label>
                                        <input type="datetime-local" wire:model="discount_ends_at" class="{{ $inputClass }}">
                                        @error('discount_ends_at') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-400">Leave start/end blank to run indefinitely until you remove it.</p>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="cancelEdit" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                                    <button type="button" wire:click="save" class="flex-1 rounded-lg bg-emerald-600 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Save</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400">No price packages set up for this stay yet.</p>
                @endforelse
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No short-stay properties to manage yet.
        </div>
    @endforelse
</div>
