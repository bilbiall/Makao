@props(['title' => null])
@php
    $user = auth()->user();
    $role = $user->role;
    $tabItems = \App\Support\AppNavigation::tabItems($role);
    $moreItems = \App\Support\AppNavigation::moreItems($role);
    $allItems = \App\Support\AppNavigation::forRole($role);
    $profileRoute = \App\Support\AppNavigation::profileRoute($role);
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full bg-stone-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title . ' - Renty' : 'Renty' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body data-has-livewire="true" class="h-full bg-stone-50 text-slate-900 antialiased" x-data="{ moreOpen: false }">

    {{-- Desktop sidebar --}}
    <aside class="hidden lg:flex lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 lg:flex-col lg:border-r lg:border-slate-200 lg:bg-white">
        <div class="flex items-center gap-2 px-6 py-5 border-b border-slate-100">
            <span class="text-xl font-bold text-emerald-700">Renty</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @foreach ($allItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   @class([
                       'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                       'bg-emerald-50 text-emerald-700' => $active,
                       'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => !$active,
                   ])>
                    @svg($item['icon'], 'w-5 h-5 flex-shrink-0')
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="border-t border-slate-100 p-4">
            <a href="{{ route($profileRoute) }}" class="flex items-center gap-3 rounded-lg -m-1 p-1 hover:bg-slate-50 transition">
                <div class="h-9 w-9 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-semibold flex-shrink-0">
                    {{ collect(explode(' ', $user->name))->map(fn ($p) => strtoupper($p[0] ?? ''))->take(2)->implode('') }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-900 truncate">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500 capitalize">{{ $role }}</p>
                </div>
            </a>
            <form method="POST" action="{{ route('app.logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full text-left text-xs font-medium text-slate-500 hover:text-rose-600 transition">
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- Top bar. Margin (not padding) for the sidebar offset - padding would only
         shift this header's CONTENT right while its own translucent background box
         still spans the full width, painting over the sidebar's own logo beneath it. --}}
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200 lg:ml-64">
        <div class="flex items-center justify-between px-4 py-3 lg:px-8">
            <div class="flex items-center gap-2 lg:hidden">
                <span class="text-lg font-bold text-emerald-700">Renty</span>
            </div>
            <h1 class="hidden lg:block text-lg font-semibold text-slate-900">{{ $title }}</h1>
            <div class="flex items-center gap-3">
                <button class="relative p-2 rounded-full text-slate-500 hover:bg-slate-100 transition" aria-label="Notifications">
                    @svg('heroicon-o-bell', 'w-5 h-5')
                </button>
                <a href="{{ route($profileRoute) }}" class="h-8 w-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-semibold">
                    {{ collect(explode(' ', $user->name))->map(fn ($p) => strtoupper($p[0] ?? ''))->take(2)->implode('') }}
                </a>
            </div>
        </div>
    </header>

    {{-- Main content --}}
    <main class="lg:pl-64 pb-24 lg:pb-8">
        <div class="px-4 py-5 lg:px-8 lg:py-6 max-w-5xl mx-auto">
            @if ($title)
                <h1 class="lg:hidden text-2xl font-bold text-slate-900 mb-5">{{ $title }}</h1>
            @endif
            {{ $slot }}
        </div>
    </main>

    {{-- Mobile bottom tab bar --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200 pb-[env(safe-area-inset-bottom)]">
        {{-- Always 5 columns: tabItems() is capped at 4 + this trailing "More" button.
             A literal, static class is required here - Tailwind's build-time scanner
             can't see a runtime-interpolated "grid-cols-{{ $n }}" as a real utility. --}}
        <div class="grid grid-cols-5">
            @foreach ($tabItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" class="flex flex-col items-center justify-center gap-1 py-2.5 text-[11px] font-medium {{ $active ? 'text-emerald-700' : 'text-slate-500' }}">
                    @svg($item['icon'], 'w-6 h-6')
                    {{ $item['label'] }}
                </a>
            @endforeach
            <button @click="moreOpen = true" class="flex flex-col items-center justify-center gap-1 py-2.5 text-[11px] font-medium text-slate-500">
                @svg('heroicon-o-ellipsis-horizontal-circle', 'w-6 h-6')
                More
            </button>
        </div>
    </nav>

    {{-- Mobile "More" sheet --}}
    <div x-show="moreOpen" x-cloak class="lg:hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-slate-900/40" @click="moreOpen = false"></div>
        <div x-show="moreOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl border-t border-slate-200 pb-[env(safe-area-inset-bottom)]">
            <div class="mx-auto mt-3 h-1.5 w-10 rounded-full bg-slate-300"></div>
            <div class="px-4 py-4 grid grid-cols-3 gap-3">
                @foreach ($moreItems as $item)
                    <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-2 rounded-xl border border-slate-100 py-4 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        @svg($item['icon'], 'w-6 h-6 text-emerald-600')
                        <span class="text-center">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
            <form method="POST" action="{{ route('app.logout') }}" class="border-t border-slate-100 px-4 py-3">
                @csrf
                <button type="submit" class="w-full text-center text-sm font-medium text-rose-600">Sign out</button>
            </form>
        </div>
    </div>

    @livewireScripts
</body>
</html>
