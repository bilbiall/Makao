<x-layouts.marketing>
    {{-- Hero --}}
    <section class="relative isolate">
        <img src="{{ asset('images/nairobi-skyline-hero.jpg') }}" alt="Nairobi skyline at golden hour" class="absolute inset-0 h-full w-full object-cover">
        <div class="hero-scrim absolute inset-0"></div>
        <div class="relative mx-auto max-w-6xl px-4 pb-8 pt-16 sm:px-6 sm:pb-12 sm:pt-24">
            <h1 class="mt-4 max-w-2xl text-4xl font-semibold leading-tight tracking-tight text-white sm:text-5xl">
                Find a home in Kenya you can actually trust.
            </h1>
            <p class="mt-3 max-w-xl text-base text-slate-200 sm:text-lg">
                Long-term rentals and furnished short stays - browse real listings, request a viewing or book
                online, and move in without the guesswork.
            </p>

            <div class="mt-8 sm:mt-12">
                <x-marketing.search-widget :areas="$areas" />
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">How Makao works</h2>
        <p class="mt-2 max-w-xl text-slate-500 dark:text-slate-400">Three steps from searching to keys in hand.</p>
        <ol class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['icon' => 'heroicon-o-magnifying-glass', 'title' => 'Search listings', 'body' => 'Filter by area, type and budget across long-term homes and short stays.'],
                ['icon' => 'heroicon-o-calendar-days', 'title' => 'Request a viewing or book instantly', 'body' => 'Long-term homes get a scheduled viewing. Furnished stays can be booked on the spot.'],
                ['icon' => 'heroicon-o-key', 'title' => 'Move in or check in', 'body' => 'Agree terms in the app, pay securely and collect your keys.'],
            ] as $i => $step)
                <li class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="grid h-11 w-11 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                        @svg($step['icon'], 'w-5 h-5')
                    </div>
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Step {{ $i + 1 }}</p>
                    <h3 class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Featured listings --}}
    <section class="mx-auto max-w-6xl px-4 pb-14 sm:px-6 sm:pb-20">
        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-4">
            <div class="min-w-0">
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">Recently added</h2>
                <p class="mt-2 text-slate-500 dark:text-slate-400">A mix of long-term homes and furnished stays.</p>
            </div>
            <a href="{{ route('listings.index') }}" class="shrink-0 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                View all
            </a>
        </div>
        @if ($featured->isEmpty())
            <p class="mt-8 text-sm text-slate-500 dark:text-slate-400">No listings published yet - check back soon.</p>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $house)
                    <x-listings.card :house="$house" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Trust --}}
    <section class="border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                        No fake listings, no phantom viewing fees.
                    </h2>
                    <p class="mt-3 text-slate-500 dark:text-slate-400">
                        Every listing you see is managed by a real property owner through Makao's own management platform -
                        vacancy is live, not a stale post from three months ago.
                    </p>
                    <a href="{{ route('listings.index') }}" class="mt-6 inline-flex rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                        Browse homes
                    </a>
                </div>
                <ul class="grid gap-4 md:grid-cols-3 lg:grid-cols-1">
                    @foreach ([
                        ['icon' => 'heroicon-o-camera', 'title' => 'Real photos', 'body' => 'Photos are uploaded by the property owner managing the actual unit - no stock images.'],
                        ['icon' => 'heroicon-o-arrow-path', 'title' => 'Live availability', 'body' => 'A unit disappears from search the moment it\'s no longer vacant.'],
                        ['icon' => 'heroicon-o-banknotes', 'title' => 'Honest pricing', 'body' => 'Rent, deposit and short-stay rates come straight from the property owner\'s own records.'],
                    ] as $t)
                        <li class="rounded-2xl border border-slate-200 bg-stone-50 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-800/50">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    @svg($t['icon'], 'w-5 h-5')
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $t['title'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $t['body'] }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- For property owners --}}
    <section class="bg-stone-50 dark:bg-slate-950">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
                <div>
                    <span class="inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                        For property owners
                    </span>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-3xl">
                        Own a place? List and manage it from right here.
                    </h2>
                    <p class="mt-3 text-slate-500 dark:text-slate-400">
                        There's no separate system to juggle - the same dashboard you use to list a vacancy also
                        collects rent, messages tenants and tracks every unit, on the very platform renters are
                        searching right now.
                    </p>
                    <a href="{{ route('get-started') }}" class="mt-6 inline-flex rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                        List your property
                    </a>
                </div>
                <ul class="grid gap-4 md:grid-cols-3 lg:grid-cols-1">
                    @foreach ([
                        ['icon' => 'heroicon-o-building-office-2', 'title' => 'List once, reach renters instantly', 'body' => 'A vacant unit you add shows up in search the moment it\'s published - no separate listing site to maintain.'],
                        ['icon' => 'heroicon-o-squares-2x2', 'title' => 'One dashboard for everything', 'body' => 'Rent collection, tenant messaging, maintenance and move-outs, alongside the listing itself - not a separate tool.'],
                        ['icon' => 'heroicon-o-banknotes', 'title' => 'Get paid via M-Pesa or Pesapal', 'body' => 'Tenants pay in-app, straight to the method you set up for your business.'],
                    ] as $t)
                        <li class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    @svg($t['icon'], 'w-5 h-5')
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $t['title'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $t['body'] }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Closing CTA --}}
    <section class="bg-emerald-600 dark:bg-emerald-700">
        <div class="mx-auto max-w-6xl px-4 py-14 text-center sm:px-6 sm:py-20">
            <h2 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Your next place is a search away.</h2>
            <p class="mx-auto mt-3 max-w-xl text-emerald-50">
                Start with a long-term home, or book a furnished apartment for the night.
            </p>
            <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('listings.index') }}" class="rounded-lg bg-white px-6 py-3 text-sm font-semibold text-emerald-700 transition-colors hover:bg-emerald-50">
                    Rent long-term
                </a>
                <a href="{{ route('stays.index') }}" class="rounded-lg border border-emerald-300/60 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                    Find a BnB
                </a>
            </div>
        </div>
    </section>
</x-layouts.marketing>
