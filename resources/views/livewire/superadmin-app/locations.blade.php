@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
@endphp
<div class="space-y-4">
    @if (session('city-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('city-saved') }}
        </div>
    @endif

    {{-- Coverage report - the same numbers as Filament's CityResource list page. --}}
    <div class="grid grid-cols-3 gap-2 text-center">
        <div class="rounded-xl bg-white border border-slate-200 py-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $totalCities }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Total towns</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 py-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-lg font-semibold text-emerald-700 dark:text-emerald-400">{{ $openCities }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Open to landlords</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 py-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $configuredCities }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Have neighbourhoods</p>
        </div>
    </div>

    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search towns..." class="{{ $inputClass }}">

    @if (!$showForm)
        <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
            + Add a town
        </button>
    @else
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">New town</p>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Name</label>
                <input type="text" wire:model="name" placeholder="e.g. Kericho" class="{{ $inputClass }}">
                @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <p class="text-xs text-slate-400 dark:text-slate-500">Starts closed - turn it on below once you're ready for landlords to pick it.</p>
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="createCity" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
            </div>
        </div>
    @endif

    <div class="space-y-2">
        @forelse ($cities as $city)
            <div class="flex items-center justify-between gap-3 rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="min-w-0">
                    <p class="font-medium text-slate-900 dark:text-slate-100 truncate">{{ $city->name }}</p>
                    <p class="text-xs mt-0.5 {{ $city->areas_count > 0 ? 'text-slate-500 dark:text-slate-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $city->areas_count > 0 ? "{$city->areas_count} neighbourhoods set up" : 'No neighbourhoods yet' }}
                    </p>
                </div>

                {{-- Toggle switch - wire:click flips it directly, no separate save step. --}}
                <button
                    type="button"
                    wire:click="toggleOpen({{ $city->id }})"
                    role="switch"
                    aria-checked="{{ $city->is_open ? 'true' : 'false' }}"
                    aria-label="{{ $city->name }} is {{ $city->is_open ? 'open' : 'closed' }} to landlords"
                    @class([
                        'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors',
                        'bg-emerald-600' => $city->is_open,
                        'bg-slate-300 dark:bg-slate-700' => !$city->is_open,
                    ])
                >
                    <span @class([
                        'inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform',
                        'translate-x-6' => $city->is_open,
                        'translate-x-1' => !$city->is_open,
                    ])></span>
                </button>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No towns match "{{ $search }}".
            </div>
        @endforelse
    </div>

    <div>{{ $cities->links() }}</div>
</div>
