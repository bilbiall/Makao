@php
    // Only the homepage opens on a full-bleed hero photo - there the nav starts
    // transparent/light-text so the photo shows through behind it, then swaps to a
    // solid bar once scrolled. Every other page has no hero to float over, so the
    // nav stays solid and in-flow (sticky) throughout.
    $floatsOverHero = request()->routeIs('home');
@endphp
<header
    x-data="{ scrolled: {{ $floatsOverHero ? 'false' : 'true' }} }"
    @if ($floatsOverHero)
        x-init="scrolled = window.scrollY > 10; window.addEventListener('scroll', () => scrolled = window.scrollY > 10)"
    @endif
    @class([
        'inset-x-0 top-0 z-40 transition-colors duration-300',
        'fixed' => $floatsOverHero,
        'sticky' => !$floatsOverHero,
    ])
    :class="scrolled ? 'bg-stone-50/90 dark:bg-slate-950/90 backdrop-blur border-b border-slate-200 dark:border-slate-800' : 'bg-transparent border-b border-transparent'"
>
    <div class="mx-auto grid max-w-6xl grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2">
            <x-brand-logo imgClass="h-8" textClass="truncate text-lg font-semibold tracking-tight transition-colors" />
        </a>

        <nav class="flex items-center gap-1 sm:gap-2">
            <a href="{{ route('listings.index') }}"
               class="hidden rounded-lg px-3 py-2 text-sm font-medium transition-colors sm:block"
               :class="scrolled ? '{{ request()->routeIs('listings.*') ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-400' }}' : 'text-white/90 hover:text-white'">
                Rent Long-Term
            </a>
            <a href="{{ route('stays.index') }}"
               class="hidden rounded-lg px-3 py-2 text-sm font-medium transition-colors sm:block"
               :class="scrolled ? '{{ request()->routeIs('stays.*') || request()->routeIs('bookings.*') ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-400' }}' : 'text-white/90 hover:text-white'">
                Find a BnB
            </a>
            <x-theme-toggle class="h-9 w-9 text-slate-400 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:bg-slate-800" />
            <a href="{{ route('generic.login') }}"
               class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
               :class="scrolled ? 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800' : 'border-white/40 bg-transparent text-white hover:border-white/70'">
                Log in
            </a>
            <a href="{{ route('get-started') }}" class="hidden rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 sm:block">
                Get started
            </a>
        </nav>
    </div>
</header>
