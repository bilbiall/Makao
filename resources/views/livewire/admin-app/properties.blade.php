@php $fieldClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp
<div class="space-y-4">
    @if (session('properties-status'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('properties-status') }}
        </div>
    @endif
    @if (session('properties-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('properties-error') }}
        </div>
    @endif

    @if ($this->canManageProperties())
        @if (!$showPropertyForm)
            <button wire:click="$set('showPropertyForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
                + Add property
            </button>
        @else
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">New property</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">A property is a building or compound - you'll add individual units to it next.</p>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Property name</label>
                    <input type="text" wire:model="location_name" placeholder="e.g. Greenview Apartments" class="{{ $fieldClass }}">
                    @error('location_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Area / town</label>
                    <input type="text" wire:model="geo_id" placeholder="e.g. Kilimani" class="{{ $fieldClass }}">
                </div>
                <div class="flex gap-3">
                    <button wire:click="$set('showPropertyForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                    <button wire:click="createProperty" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
                </div>
            </div>
        @endif
    @endif

    @forelse ($locations as $location)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $location->location_name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $location->geo_id ? $location->geo_id . ' · ' : '' }}
                        <a href="{{ route('app.admin.units', ['propertyFilter' => $location->id]) }}" class="hover:underline">{{ $location->houses->count() }} units</a>
                    </p>
                </div>
                <button wire:click="startAddingHouse({{ $location->id }})" class="text-xs font-semibold text-emerald-700 hover:underline whitespace-nowrap">
                    + Add unit
                </button>
            </div>

            @if ($addingHouseTo === $location->id)
                <div class="p-4 bg-stone-50 border-b border-slate-100 space-y-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Unit name/number</label>
                        <input type="text" wire:model="house_name" placeholder="e.g. A1, Bedsitter 3" class="{{ $fieldClass }}">
                        @error('house_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Unit type</label>
                        <select wire:model="house_type" class="{{ $fieldClass }}">
                            <option value="">Select a type</option>
                            @foreach ($unitTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('house_type') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Listing type</label>
                        <select wire:model.live="listing_mode" class="{{ $fieldClass }}">
                            <option value="long_term">Long-term rental</option>
                            <option value="short_term">Short-stay (BnB)</option>
                        </select>
                    </div>

                    @if ($listing_mode === 'long_term')
                        <div>
                            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Monthly rent (KES)</label>
                            <input type="number" wire:model="rent_amount" class="{{ $fieldClass }}">
                            @error('rent_amount') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="space-y-2">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Short-stay units are priced per night/week/month instead of a fixed monthly rent. Fill in at least one.</p>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Nightly (KES)</label>
                                    <input type="number" wire:model="bnb_nightly_price" class="{{ $fieldClass }}">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Weekly (KES)</label>
                                    <input type="number" wire:model="bnb_weekly_price" class="{{ $fieldClass }}">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Monthly (KES)</label>
                                    <input type="number" wire:model="bnb_monthly_price" class="{{ $fieldClass }}">
                                </div>
                            </div>
                            @error('bnb_nightly_price') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <button wire:click="$set('addingHouseTo', null)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                        <button wire:click="createHouse" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No properties yet.
        </div>
    @endforelse
</div>
