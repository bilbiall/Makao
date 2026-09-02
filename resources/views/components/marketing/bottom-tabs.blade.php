@php
    // Each role's own app-shell home - matches the routes already wired in routes/web.php.
    $accountRoute = auth()->check() ? match (auth()->user()->role) {
        'tenant' => 'app.tenant.dashboard',
        'superadmin' => 'app.superadmin.dashboard',
        'agent' => 'app.admin.bookings',
        'user' => 'app.user.dashboard',
        default => 'app.admin.dashboard',
    } : 'generic.login';

    $tabs = [
        ['route' => 'home', 'label' => 'Home', 'icon' => 'heroicon-o-home'],
        ['route' => 'listings.index', 'label' => 'Rent', 'icon' => 'heroicon-o-magnifying-glass'],
        ['route' => 'stays.index', 'label' => 'BnB', 'icon' => 'heroicon-o-calendar-days'],
        ['route' => 'app.user.watchlist', 'label' => 'Saved', 'icon' => 'heroicon-o-heart'],
        ['route' => $accountRoute, 'label' => 'Account', 'icon' => 'heroicon-o-user-circle'],
    ];
@endphp
<nav class="md:hidden fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
    <ul class="mx-auto grid max-w-md grid-cols-5">
        @foreach ($tabs as $tab)
            @php $active = request()->routeIs($tab['route']) || ($tab['route'] === 'home' && request()->routeIs('home')); @endphp
            <li>
                <a href="{{ route($tab['route']) }}" @class(['flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium', 'text-emerald-700 dark:text-emerald-400' => $active, 'text-slate-500 dark:text-slate-400' => !$active])>
                    @svg($tab['icon'], 'w-5 h-5')
                    {{ $tab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
    <div class="h-[env(safe-area-inset-bottom)]"></div>
</nav>
