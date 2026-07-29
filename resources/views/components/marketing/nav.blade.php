@php
    // Only the homepage opens on a full-bleed dark photo (the hero) - there the nav
    // starts transparent/light-text so the photo shows through behind it, then swaps
    // to the normal solid bar once the user scrolls past the hero. Every other
    // marketing page opens on plain light content, so the nav stays solid throughout,
    // exactly as before.
    $floatsOverHero = request()->routeIs('home');
@endphp
<header
    x-data="{ open: false, scrolled: {{ $floatsOverHero ? 'false' : 'true' }} }"
    @if ($floatsOverHero)
        x-init="scrolled = window.scrollY > 10; window.addEventListener('scroll', () => scrolled = window.scrollY > 10)"
    @endif
    class="{{ $floatsOverHero ? 'fixed' : 'sticky' }} top-0 inset-x-0 z-50 transition-colors duration-300"
    :class="scrolled ? 'bg-stone-50/90 backdrop-blur border-b border-slate-200' : 'bg-transparent border-b border-transparent'"
>
    <nav class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="{{ url('/') }}" class="text-xl font-bold transition-colors" :class="scrolled ? 'text-slate-900' : 'text-white'">Renty</a>

        <div class="hidden md:flex items-center gap-8 text-sm font-medium transition-colors" :class="scrolled ? 'text-slate-600' : 'text-white/90'">
            <a href="{{ url('/#features') }}" :class="scrolled ? 'hover:text-slate-900' : 'hover:text-white'">Features</a>
            <a href="{{ route('pricing') }}" :class="scrolled ? 'hover:text-slate-900' : 'hover:text-white'">Pricing</a>
            <a href="{{ route('generic.login') }}" :class="scrolled ? 'hover:text-slate-900' : 'hover:text-white'">Log in</a>
        </div>

        <div class="hidden md:block">
            <a href="{{ route('signup') }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                Start Free Trial
            </a>
        </div>

        <button @click="open = !open" class="md:hidden p-2 transition-colors" :class="scrolled ? 'text-slate-700' : 'text-white'" aria-label="Toggle menu">
            <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </nav>

    <div x-show="open" x-cloak x-transition class="md:hidden border-t border-slate-200 bg-stone-50 px-6 py-4 space-y-3 text-sm font-medium text-slate-600">
        <a href="{{ url('/#features') }}" class="block hover:text-slate-900">Features</a>
        <a href="{{ route('pricing') }}" class="block hover:text-slate-900">Pricing</a>
        <a href="{{ route('generic.login') }}" class="block hover:text-slate-900">Log in</a>
        <a href="{{ route('signup') }}" class="block rounded-lg bg-emerald-600 px-5 py-2.5 text-center font-semibold text-white">Start Free Trial</a>
    </div>
</header>
