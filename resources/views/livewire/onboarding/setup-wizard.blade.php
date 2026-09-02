<div class="space-y-5">
    <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Step {{ $step }} of 2</p>
        <p class="text-xl font-bold text-slate-900 dark:text-slate-100">
            {{ $step === 1 ? 'Add your first property' : 'Add your first unit' }}
        </p>
    </div>

    @if ($step === 1)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">A property is a building or compound - e.g. "Greenview Apartments" or "Kahawa West Compound". You'll add individual units to it next.</p>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Property name</label>
                <input type="text" wire:model="location_name" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" placeholder="e.g. Greenview Apartments">
                @error('location_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Area / town (optional)</label>
                <x-area-search-input
                    :cities="$cities"
                    wire-model="geo_id"
                    placeholder="e.g. Kahawa West, Nairobi"
                    input-class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                />
            </div>
            <button wire:click="createLocation" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                Continue
            </button>
        </div>
    @else
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm text-slate-500 dark:text-slate-400">A unit is a single rentable space - a bedsitter, 1 bedroom, etc.</p>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Unit name/number</label>
                <input type="text" wire:model="house_name" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" placeholder="e.g. A1, Bedsitter 3">
                @error('house_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Unit type</label>
                <select wire:model="house_type" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Select a type</option>
                    @foreach ($unitTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                @error('house_type') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Monthly rent (KES)</label>
                <input type="number" wire:model="rent_amount" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('rent_amount') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <button wire:click="createHouse" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                Finish setup
            </button>
        </div>
    @endif

    <button wire:click="skip" class="w-full text-center text-sm text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
        Skip for now
    </button>
</div>
