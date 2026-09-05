@props(['title' => null, 'hideHeading' => false])
@php
    $user = auth()->user();
    $role = $user->role;
    $tabItems = \App\Support\AppNavigation::tabItems($role);
    $moreItems = \App\Support\AppNavigation::moreItems($role);
    $allItems = \App\Support\AppNavigation::forRole($role);
    $profileRoute = \App\Support\AppNavigation::profileRoute($role);
    $filamentRoute = \App\Support\AppNavigation::filamentDashboardRoute($role);
    $brandPalette = \App\Models\Setting::forLandlord(null)->payload['brand_palette'] ?? 'green';
@endphp
<!DOCTYPE html>
<html lang="en" data-palette="{{ $brandPalette }}" class="h-full bg-stone-50 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    @include('partials.theme-init-script')
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.favicon')
    <title>{{ $title ? $title . ' - Renty' : 'Renty' }}</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#047857">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body data-has-livewire="true" class="h-full bg-stone-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" x-data="{ moreOpen: false, loading: false }">

    {{-- Global "something is loading" bar - fires for every Livewire request on the
         page (see resources/js/app.js), so a Save/action click never looks hung even
         without a per-button wire:loading directive. --}}
    <div
        x-show="loading"
        x-on:app:loading-start.window="loading = true"
        x-on:app:loading-end.window="loading = false"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed top-0 inset-x-0 z-[100] h-1 overflow-hidden bg-emerald-100 dark:bg-emerald-500/10"
    >
        <div class="h-full w-1/3 bg-emerald-600 dark:bg-emerald-400 animate-[loading-bar_1s_ease-in-out_infinite]"></div>
    </div>

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:flex lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 lg:flex-col lg:border-r lg:border-slate-200 lg:bg-white dark:lg:border-slate-800 dark:lg:bg-slate-900">
        <div class="flex items-center justify-between gap-2 px-6 py-5 border-b border-slate-100 dark:border-slate-800">
            <x-brand-logo imgClass="h-9" textClass="text-xl font-bold text-emerald-700 dark:text-emerald-400" />
            <x-theme-toggle class="h-8 w-8 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300" />
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @foreach ($allItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                       'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $active,
                       'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100' => !$active,
                   ])>
                    @svg($item['icon'], 'w-5 h-5 flex-shrink-0')
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if ($item['label'] === 'Chat')
                        <livewire:chat-unread-badge :key="'chat-badge-sidebar'" />
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="border-t border-slate-100 p-4 dark:border-slate-800">
            <a href="{{ route($profileRoute) }}" class="flex items-center gap-3 rounded-lg -m-1 p-1 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                <div class="h-9 w-9 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-semibold flex-shrink-0">
                    {{ collect(explode(' ', $user->name))->map(fn ($p) => strtoupper($p[0] ?? ''))->take(2)->implode('') }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ \App\Support\AppNavigation::roleLabel($role) }}</p>
                </div>
            </a>
            @if ($filamentRoute)
                <a href="{{ route($filamentRoute) }}" class="mt-3 flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-400 transition">
                    @svg('heroicon-o-computer-desktop', 'w-3.5 h-3.5')
                    Advanced view
                </a>
            @endif
            <form method="POST" action="{{ route('app.logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full text-left text-xs font-medium text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400 transition">
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- Top bar. Margin (not padding) for the sidebar offset - padding would only
         shift this header's CONTENT right while its own translucent background box
         still spans the full width, painting over the sidebar's own logo beneath it. --}}
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200 dark:bg-slate-900/90 dark:border-slate-800 lg:ml-64">
        <div class="flex items-center justify-between px-4 py-3 lg:px-8">
            <div class="flex items-center gap-2 lg:hidden">
                <x-brand-logo imgClass="h-8" textClass="text-lg font-bold text-emerald-700 dark:text-emerald-400" />
            </div>
            @if ($title && !$hideHeading)
                <h1 class="hidden lg:block text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h1>
            @else
                <div></div>
            @endif
            <div class="flex items-center gap-3">
                <x-theme-toggle class="h-8 w-8 lg:hidden text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300" />
                <livewire:notification-bell />
                <a href="{{ route($profileRoute) }}" class="h-8 w-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-semibold">
                    {{ collect(explode(' ', $user->name))->map(fn ($p) => strtoupper($p[0] ?? ''))->take(2)->implode('') }}
                </a>
            </div>
        </div>
    </header>

    {{-- Main content --}}
    <main class="lg:pl-64 pb-24 lg:pb-8">
        <div class="px-4 py-5 lg:px-8 lg:py-6 max-w-5xl mx-auto">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
                    {{ session('status') }}
                </div>
            @endif

            @if (!$user->hasVerifiedEmail())
                <div class="mb-4 flex flex-col gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400 sm:flex-row sm:items-center sm:justify-between">
                    <span>Verify your email to unlock every feature - check your inbox for the link we sent to {{ $user->email }}.</span>
                    <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="whitespace-nowrap rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-transparent dark:text-amber-400 dark:hover:bg-amber-500/10">
                            Resend email
                        </button>
                    </form>
                </div>
            @endif

            @if ($title && !$hideHeading)
                <h1 class="lg:hidden text-2xl font-bold text-slate-900 dark:text-slate-100 mb-5">{{ $title }}</h1>
            @endif
            {{ $slot }}
        </div>
    </main>

    {{-- Mobile bottom tab bar --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200 dark:bg-slate-900/95 dark:border-slate-800 pb-[env(safe-area-inset-bottom)]">
        {{-- Always 5 columns: tabItems() is capped at 4 + this trailing "More" button.
             A literal, static class is required here - Tailwind's build-time scanner
             can't see a runtime-interpolated "grid-cols-{{ $n }}" as a real utility. --}}
        <div class="grid grid-cols-5">
            @foreach ($tabItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" class="flex flex-col items-center justify-center gap-1 py-2.5 text-[11px] font-medium {{ $active ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                    <span class="relative">
                        @svg($item['icon'], 'w-6 h-6')
                        @if ($item['label'] === 'Chat')
                            <span class="absolute -top-1 -right-1.5"><livewire:chat-unread-badge :key="'chat-badge-tab'" /></span>
                        @endif
                    </span>
                    {{ $item['label'] }}
                </a>
            @endforeach
            <button @click="moreOpen = true" class="flex flex-col items-center justify-center gap-1 py-2.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                @svg('heroicon-o-ellipsis-horizontal-circle', 'w-6 h-6')
                More
            </button>
        </div>
    </nav>

    {{-- Mobile "More" sheet --}}
    <div x-show="moreOpen" x-cloak class="lg:hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-slate-900/40" @click="moreOpen = false"></div>
        <div x-show="moreOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl border-t border-slate-200 dark:bg-slate-900 dark:border-slate-800 pb-[env(safe-area-inset-bottom)]">
            <div class="mx-auto mt-3 h-1.5 w-10 rounded-full bg-slate-300 dark:bg-slate-700"></div>
            <div class="px-4 py-4 grid grid-cols-3 gap-3">
                @foreach ($moreItems as $item)
                    <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-2 rounded-xl border border-slate-100 py-4 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800">
                        <span class="relative">
                            @svg($item['icon'], 'w-6 h-6 text-emerald-600 dark:text-emerald-400')
                            @if ($item['label'] === 'Chat')
                                <span class="absolute -top-1 -right-1.5"><livewire:chat-unread-badge :key="'chat-badge-more'" /></span>
                            @endif
                        </span>
                        <span class="text-center">{{ $item['label'] }}</span>
                    </a>
                @endforeach
                <button type="button" x-data @click="
                        const html = document.documentElement;
                        const nowDark = !html.classList.contains('dark');
                        html.classList.toggle('dark', nowDark);
                        try { localStorage.setItem('theme', nowDark ? 'dark' : 'light'); } catch (e) {}
                    " class="flex flex-col items-center gap-2 rounded-xl border border-slate-100 py-4 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800">
                    <span class="dark:hidden">@svg('heroicon-o-moon', 'w-6 h-6 text-emerald-600')</span>
                    <span class="hidden dark:inline">@svg('heroicon-o-sun', 'w-6 h-6 text-emerald-400')</span>
                    <span class="text-center"><span class="dark:hidden">Dark mode</span><span class="hidden dark:inline">Light mode</span></span>
                </button>
            </div>
            @if ($filamentRoute)
                <a href="{{ route($filamentRoute) }}" class="flex items-center justify-center gap-1.5 border-t border-slate-100 dark:border-slate-800 px-4 py-3 text-sm font-medium text-slate-600 dark:text-slate-300">
                    @svg('heroicon-o-computer-desktop', 'w-4 h-4')
                    Advanced view
                </a>
            @endif
            <form method="POST" action="{{ route('app.logout') }}" class="border-t border-slate-100 dark:border-slate-800 px-4 py-3">
                @csrf
                <button type="submit" class="w-full text-center text-sm font-medium text-rose-600 dark:text-rose-400">Sign out</button>
            </form>
        </div>
    </div>

    @livewireScripts

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
            });
        }
    </script>
</body>
</html>
