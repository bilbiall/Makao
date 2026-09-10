@php $fieldClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp
<div class="space-y-4">
    @if (session('unit-success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('unit-success') }}
        </div>
    @endif
    @if (session('unit-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('unit-error') }}
        </div>
    @endif

    <div class="flex gap-2">
        @if ($this->canCreateProperties())
            <button wire:click="startAddUnit" class="flex-1 rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
                + Add unit
            </button>
        @endif
        <button type="button" wire:click="exportUnits" class="flex items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 px-4 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
            @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
        </button>
    </div>

    @if ($showAddUnit)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingUnitId ? 'Edit unit' : 'New unit' }}</p>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Property</label>
                <select wire:model="unit_property_id" class="{{ $fieldClass }}">
                    <option value="">Select a property</option>
                    @foreach ($properties as $property)
                        <option value="{{ $property->id }}">{{ $property->location_name }}</option>
                    @endforeach
                </select>
                @error('unit_property_id') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Unit name</label>
                <input type="text" wire:model="unit_name" placeholder="e.g. A1, Bedsitter 3" class="{{ $fieldClass }}">
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">For your own records - not shown to renters.</p>
                @error('unit_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Listing display name (optional)</label>
                <input type="text" wire:model="unit_display_name" placeholder="e.g. Spacious Bedsitter Near Town" class="{{ $fieldClass }}">
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">What renters see on the public listing, if different from the name above.</p>
                @error('unit_display_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Unit type</label>
                <select wire:model="unit_type" class="{{ $fieldClass }}">
                    <option value="">Select a type</option>
                    @foreach ($unitTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
                @error('unit_type') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Listing type</label>
                <select wire:model.live="unit_listing_mode" class="{{ $fieldClass }}">
                    <option value="long_term">Rental (monthly rent)</option>
                    <option value="short_term">BnB (short-term)</option>
                </select>
            </div>

            @if ($unit_listing_mode === 'long_term')
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Monthly rent (KES)</label>
                    <input type="number" wire:model="unit_rent_amount" class="{{ $fieldClass }}">
                    @error('unit_rent_amount') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Nightly (KES)</label>
                        <input type="number" wire:model="unit_bnb_nightly" class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Weekly (KES)</label>
                        <input type="number" wire:model="unit_bnb_weekly" class="{{ $fieldClass }}">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Monthly (KES)</label>
                        <input type="number" wire:model="unit_bnb_monthly" class="{{ $fieldClass }}">
                    </div>
                </div>
                @error('unit_bnb_nightly') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                <p class="text-xs text-slate-500 dark:text-slate-400">Set at least one BnB price.</p>
            @endif

            @if ($editingUnitId)
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Status</label>
                    <select wire:model="unit_status" class="{{ $fieldClass }}">
                        <option value="Vacant">Vacant</option>
                        <option value="Occupied">Occupied</option>
                        <option value="Unavailable">Unavailable (e.g. renovating)</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
                    <input type="checkbox" wire:model="unit_is_published" class="rounded border-slate-300 dark:border-slate-700">
                    Listed on the public site
                </label>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Description</label>
                    <textarea wire:model="unit_description" rows="3" class="{{ $fieldClass }}"></textarea>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Amenities</label>
                    <div class="mt-1 grid grid-cols-2 gap-1.5 max-h-40 overflow-y-auto">
                        @foreach ($amenityOptions as $amenity)
                            <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                                <input type="checkbox" wire:model="unit_amenities" value="{{ $amenity }}" class="rounded border-slate-300 dark:border-slate-700">
                                {{ $amenity }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Nearby places (minutes away)</label>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        @foreach ($nearbyCategories as $slug => $label)
                            <div>
                                <label class="text-[11px] text-slate-500 dark:text-slate-400">{{ $label }}</label>
                                <input type="number" min="0" wire:model="unit_nearby.{{ $slug }}" placeholder="mins" class="{{ $fieldClass }} mt-0.5">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="cancelUnitForm" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="saveUnit" wire:loading.attr="disabled" wire:target="saveUnit" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">{{ $editingUnitId ? 'Save changes' : 'Add unit' }}</button>
            </div>
        </div>
    @endif

    {{-- Bulk import --}}
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        <button wire:click="$toggle('showImport')" class="w-full px-4 py-3 flex items-center justify-between text-left">
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">Bulk import units from a spreadsheet</span>
            <span class="text-slate-400 dark:text-slate-500 text-xs">{{ $showImport ? 'Hide' : 'Show' }}</span>
        </button>

        @if ($showImport)
            <div class="px-4 pb-4 space-y-4 border-t border-slate-100 dark:border-slate-800 pt-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <button wire:click="downloadTemplate" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                        Download CSV template
                    </button>
                    <div class="flex-1">
                        <input type="file" wire:model="importFile" accept=".csv,text/csv" class="{{ $fieldClass }} mt-0">
                        @error('importFile') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div wire:loading wire:target="importFile,import" class="text-xs text-slate-500 dark:text-slate-400">Uploading…</div>

                @if ($importFile)
                    <button wire:click="import" wire:loading.attr="disabled" wire:target="import" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                        Import units
                    </button>
                @endif

                @if ($importSummary)
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400 space-y-1">
                        <p>Created {{ $importSummary['properties'] }} new {{ \Illuminate\Support\Str::plural('property', $importSummary['properties']) }} and {{ $importSummary['units'] }} {{ \Illuminate\Support\Str::plural('unit', $importSummary['units']) }}.</p>
                        @if ($importSummary['units'] > 0)
                            <p class="text-xs">A CSV can't carry photos - each imported unit needs at least one added (via Edit) before it shows up on the public site, search, or the AI assistant. Look for the "No photos - hidden" tag below.</p>
                        @endif
                    </div>
                @endif

                @if (count($importErrors))
                    <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400 space-y-1">
                        <p class="font-semibold">{{ count($importErrors) }} row(s) skipped:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($importErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-xl bg-stone-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Column guide</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-slate-500 dark:text-slate-400">
                                    <th class="pr-3 pb-1.5 font-medium">Column</th>
                                    <th class="pr-3 pb-1.5 font-medium">Required?</th>
                                    <th class="pb-1.5 font-medium">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="text-slate-600 dark:text-slate-400 align-top">
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">property_name</td>
                                    <td class="pr-3 py-1.5">Yes</td>
                                    <td class="py-1.5">Matches an existing property by name (case-insensitive), or creates a new one. Only account owners/admins can create new properties this way.</td>
                                </tr>
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">area</td>
                                    <td class="pr-3 py-1.5">No</td>
                                    <td class="py-1.5">Area/town, e.g. "Kilimani". Only used when property_name creates a <em>new</em> property; ignored for an existing one.</td>
                                </tr>
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">unit_name</td>
                                    <td class="pr-3 py-1.5">Yes</td>
                                    <td class="py-1.5">e.g. "A1", "Bedsitter 3".</td>
                                </tr>
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">unit_type</td>
                                    <td class="pr-3 py-1.5">Yes</td>
                                    <td class="py-1.5">e.g. "Bedsitter", "1 Bedroom", "Studio".</td>
                                </tr>
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">listing_type</td>
                                    <td class="pr-3 py-1.5">Yes</td>
                                    <td class="py-1.5">Exactly <span class="font-mono">rental</span> or <span class="font-mono">bnb</span>.</td>
                                </tr>
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">rent_amount</td>
                                    <td class="pr-3 py-1.5">Only for rental</td>
                                    <td class="py-1.5">Monthly rent in KES. Required when listing_type is "rental"; ignored for "bnb".</td>
                                </tr>
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">bnb_nightly_price</td>
                                    <td class="pr-3 py-1.5">Only for bnb*</td>
                                    <td class="py-1.5">*At least one of the three bnb_* price columns is required when listing_type is "bnb"; ignored for "rental".</td>
                                </tr>
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">bnb_weekly_price</td>
                                    <td class="pr-3 py-1.5">No</td>
                                    <td class="py-1.5">bnb only.</td>
                                </tr>
                                <tr class="border-t border-slate-200 dark:border-slate-800">
                                    <td class="pr-3 py-1.5 font-mono">bnb_monthly_price</td>
                                    <td class="pr-3 py-1.5">No</td>
                                    <td class="py-1.5">bnb only.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Rows that fail validation are skipped with a reason - everything else in the file still imports. Edit the template in Excel/Google Sheets, then export/save it as CSV before uploading.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Search + filters --}}
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by unit, type, or property..." class="{{ $fieldClass }} mt-0">
        <div class="grid grid-cols-2 gap-2">
            <select wire:model.live="propertyFilter" class="{{ $fieldClass }} mt-0 text-xs">
                <option value="">All properties</option>
                @foreach ($properties as $property)
                    <option value="{{ $property->id }}">{{ $property->location_name }}</option>
                @endforeach
            </select>
            <select wire:model.live="typeFilter" class="{{ $fieldClass }} mt-0 text-xs">
                <option value="">All unit types</option>
                @foreach ($unitTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter" class="{{ $fieldClass }} mt-0 text-xs">
                <option value="">All statuses</option>
                <option value="Vacant">Vacant</option>
                <option value="Occupied">Occupied</option>
                <option value="Unavailable">Unavailable</option>
            </select>
            <select wire:model.live="modeFilter" class="{{ $fieldClass }} mt-0 text-xs">
                <option value="">Rental & BnB</option>
                <option value="long_term">Rental only</option>
                <option value="short_term">BnB only</option>
            </select>
        </div>
    </div>

    @if (count($selectedUnitIds) && $this->canDeleteProperties())
        <div class="flex items-center justify-between rounded-xl bg-slate-100 border border-slate-200 px-4 py-2 dark:bg-slate-800 dark:border-slate-700">
            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ count($selectedUnitIds) }} selected</span>
            <button type="button" wire:click="bulkDeleteUnits" wire:confirm="Delete the selected units? Occupied units will be skipped. This can't be undone." class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline">
                Delete selected
            </button>
        </div>
    @endif

    {{-- Unit list --}}
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm divide-y divide-slate-50 dark:bg-slate-900 dark:border-slate-800 dark:divide-slate-800">
        @forelse ($units as $unit)
            <div class="flex items-center justify-between px-4 py-3 text-sm">
                <div class="flex items-center gap-3 min-w-0">
                    @if ($unit->house_status !== 'Occupied' && $this->canDeleteProperties())
                        <input type="checkbox" wire:model="selectedUnitIds" value="{{ $unit->id }}" class="rounded border-slate-300 dark:border-slate-700 shrink-0">
                    @endif
                <div class="min-w-0">
                    <p class="text-slate-900 dark:text-slate-100 font-medium truncate">
                        {{ $unit->house_name }}
                        @if ($unit->display_name)
                            <span class="font-normal text-slate-400 dark:text-slate-500">&middot; {{ $unit->display_name }}</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $unit->house_type }} · {{ $unit->location?->location_name ?? '—' }}</p>
                </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    @if ($unit->listing_mode === 'short_term')
                        <span class="text-slate-500 dark:text-slate-400 text-xs">
                            @if ($unit->pricePackages->isNotEmpty())
                                KES {{ number_format($unit->pricePackages->first()->price) }}/{{ $unit->pricePackages->first()->billing_unit }}
                            @else
                                No price set
                            @endif
                        </span>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400">BnB</span>
                    @else
                        <span class="text-slate-500 dark:text-slate-400 text-xs">KES {{ number_format($unit->rent_amount ?? 0) }}</span>
                    @endif
                    <span @class([
                        'rounded-full px-2 py-0.5 text-[11px] font-medium whitespace-nowrap',
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $unit->house_status === 'Occupied',
                        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => $unit->house_status === 'Vacant',
                        'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $unit->house_status === 'Unavailable',
                    ])>{{ $unit->house_status }}</span>
                    @if ($this->canEditProperties())
                        <button
                            wire:click="togglePublish({{ $unit->id }})"
                            wire:loading.attr="disabled"
                            wire:target="togglePublish({{ $unit->id }})"
                            title="{{ $unit->is_published ? 'Listed on the public site - click to unlist' : 'Not listed on the public site - click to list' }}"
                            @class([
                                'rounded-full px-2 py-0.5 text-[11px] font-medium whitespace-nowrap border',
                                'bg-sky-100 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-400 dark:border-sky-500/20' => $unit->is_published,
                                'bg-slate-50 text-slate-400 border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700' => !$unit->is_published,
                            ])
                        >{{ $unit->is_published ? 'Listed' : 'Unlisted' }}</button>
                    @endif
                    @if ($unit->is_published && $unit->photos_count === 0)
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-medium whitespace-nowrap bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20"
                            title="Listed, but won't actually show on the public site, search, or the AI assistant until it has at least one photo - add one via Edit.">
                            No photos - hidden
                        </span>
                    @endif
                    @if ($this->canEditProperties())
                        <button
                            wire:click="startEditUnit({{ $unit->id }})"
                            title="Edit unit"
                            class="text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-300"
                        >
                            @svg('heroicon-o-pencil-square', 'w-4 h-4')
                        </button>
                    @endif
                    @if ($unit->house_status !== 'Occupied' && $this->canDeleteProperties())
                        <button
                            wire:click="deleteUnit({{ $unit->id }})"
                            wire:confirm="Delete this unit? This can't be undone."
                            title="Delete unit"
                            class="text-slate-400 hover:text-rose-600 dark:text-slate-500 dark:hover:text-rose-400"
                        >
                            @svg('heroicon-o-trash', 'w-4 h-4')
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                No units match your search/filters.
            </div>
        @endforelse
    </div>

    {{ $units->links() }}
</div>
