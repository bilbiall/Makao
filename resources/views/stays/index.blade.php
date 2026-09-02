<x-layouts.marketing :title="'Book a stay'">
    <div class="pb-14">
        <div class="sticky top-[57px] z-30 border-b border-slate-200 bg-stone-50/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
            <div class="mx-auto max-w-6xl px-4 py-3 sm:px-6">
                <form method="GET" class="grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-center">
                    @php $fieldClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition-colors focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp
                    <x-area-search-input :cities="$cities" :counts="$counts" :value="request('area')" :input-class="$fieldClass" />
                    <input type="date" name="check_in" value="{{ request('check_in') }}" class="{{ $fieldClass }}">
                    <input type="date" name="check_out" value="{{ request('check_out') }}" class="{{ $fieldClass }}">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Furnished stays</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $houses->total() }} {{ Str::plural('stay', $houses->total()) }}
                {{ request('area') ? "in " . request('area') : 'across Kenya' }}
                @if (request('check_in') && request('check_out'))
                    &middot; {{ request('check_in') }} &rarr; {{ request('check_out') }}
                @endif
            </p>

            @if ($houses->isNotEmpty())
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($houses as $house)
                        <x-listings.card :house="$house" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $houses->links() }}</div>
            @else
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                        @svg('heroicon-o-magnifying-glass', 'w-6 h-6')
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">Nothing free for those dates</h2>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500 dark:text-slate-400">
                        Try a nearby area or shift your dates by a day - most hosts open up new nights weekly.
                    </p>
                    <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('stays.index') }}" class="rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            Clear filters
                        </a>
                        <a href="{{ route('listings.index') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            Look at long-term homes
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.marketing>
