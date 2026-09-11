<div class="space-y-5">
    @if (session('alert-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('alert-saved') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Get notified of new openings</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tell us what you're looking for and we'll email you the moment a matching listing goes live. Leave a field blank to match on everything else.</p>

        <form wire:submit="store" class="mt-4 space-y-4">
            <div>
                <p class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Unit type</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\House::UNIT_TYPES as $type)
                        <label class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs dark:border-slate-700 has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-300 dark:has-[:checked]:bg-emerald-500/10 dark:has-[:checked]:border-emerald-500/40">
                            <input type="checkbox" wire:model="house_types" value="{{ $type }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            {{ $type }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Areas</label>
                <select wire:model="areas" multiple size="4" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    @foreach ($areaOptions as $area)
                        <option value="{{ $area }}">{{ $area }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Cmd/Ctrl-click to select more than one. Leave empty for any area.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Max rent (KES)</label>
                    <input type="number" wire:model="max_rent" min="0" placeholder="Any" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Listing type</label>
                    <select wire:model="listing_mode" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Long-term & stays</option>
                        <option value="long_term">Long-term only</option>
                        <option value="short_term">Short stays only</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                Create alert
            </button>
        </form>
    </div>

    <div class="space-y-3">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Your alerts</p>

        @forelse ($alerts as $alert)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between gap-3 dark:bg-slate-900 dark:border-slate-800">
                <p class="text-sm text-slate-700 dark:text-slate-300">{{ $alert->describe() }}</p>
                <button wire:click="delete({{ $alert->id }})" wire:confirm="Remove this alert?" class="text-xs font-medium text-rose-600 hover:underline flex-shrink-0">Remove</button>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No alerts yet - create one above, or tap "Notify me" on any unavailable listing.
            </div>
        @endforelse
    </div>
</div>
