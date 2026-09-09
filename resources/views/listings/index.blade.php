<x-layouts.marketing :title="'Find a house'">
    <div class="pb-14">
        <div class="sticky top-[57px] z-30 border-b border-slate-200 bg-stone-50/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
            <div class="mx-auto max-w-6xl px-4 py-3 sm:px-6">
                <form method="GET" class="grid gap-2 sm:grid-cols-3 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-center">
                    @php $fieldClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition-colors focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp
                    <x-area-search-input :cities="$cities" :counts="$counts" :value="request('area')" :input-class="$fieldClass" />
                    <select name="house_type" class="{{ $fieldClass }}" onchange="this.form.submit()">
                        <option value="">Any type</option>
                        @foreach (['Bedsitter', 'Single Room', '1 Bedroom', '2 Bedroom', '3 Bedroom'] as $type)
                            <option value="{{ $type }}" @selected(request('house_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                    <select name="max_rent" class="{{ $fieldClass }}" onchange="this.form.submit()">
                        <option value="">Any budget</option>
                        @foreach ([20000, 50000, 90000, 150000] as $budget)
                            <option value="{{ $budget }}" @selected((int) request('max_rent') === $budget)>Up to KES {{ number_format($budget) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6" x-data="resultsMap(@js($pins))">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Homes to rent long-term</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ $houses->total() }} {{ Str::plural('home', $houses->total()) }}
                        {{ request('area') ? "in " . request('area') : 'across Kenya' }}
                    </p>
                </div>
                @if ($houses->isNotEmpty())
                    <button type="button" x-on:click="toggle()" class="mt-1 inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        <span x-show="!showMap" x-cloak>@svg('heroicon-o-map', 'w-4 h-4') Map view</span>
                        <span x-show="showMap" x-cloak>@svg('heroicon-o-squares-2x2', 'w-4 h-4') List view</span>
                    </button>
                @endif
            </div>

            @if (isset($nearbyAreas) && $nearbyAreas->isNotEmpty())
                <p class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                    <span>Also nearby:</span>
                    @foreach ($nearbyAreas as $nearbyArea)
                        <a href="{{ route('listings.index', array_merge(request()->except('area', 'page'), ['area' => $nearbyArea->name])) }}"
                           class="rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:border-emerald-600 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-emerald-500 dark:hover:text-emerald-400">
                            {{ $nearbyArea->name }} ({{ $counts[$nearbyArea->name] }})
                        </a>
                    @endforeach
                </p>
            @endif

            @if ($houses->isNotEmpty())
                <div x-show="!showMap">
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($houses as $house)
                            <x-listings.card :house="$house" :watchlisted="in_array($house->id, $watchlistedIds)" />
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $houses->links() }}</div>
                </div>
                <div x-show="showMap" x-cloak class="mt-6">
                    <div x-ref="map" class="h-[32rem] w-full rounded-2xl border border-slate-200 dark:border-slate-800"></div>
                    <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                        Only this page's results are shown - a listing appears at its exact pin if the landlord set one, otherwise at its area's approximate centre.
                    </p>
                </div>
            @else
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                        @svg('heroicon-o-magnifying-glass', 'w-6 h-6')
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">No homes match these filters yet</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                        Try widening your budget or removing the location filter - new verified homes are added every week.
                    </p>
                    <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('listings.index') }}" class="rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            Clear filters
                        </a>
                        <a href="{{ route('stays.index') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            Browse short stays
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.marketing>
