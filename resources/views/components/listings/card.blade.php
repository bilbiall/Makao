@props(['house', 'watchlisted' => false])
@php
    $isStay = $house->isShortTerm();
    $showRoute = $isStay ? route('stays.show', $house) : route('listings.show', $house);
    $cheapestPackage = $isStay ? $house->pricePackages->sortBy('price')->first() : null;
    $photo = $house->photos->first();
@endphp
<div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
    <div class="relative aspect-video overflow-hidden bg-slate-100 dark:bg-slate-800">
        @if ($photo)
            <img src="{{ $photo->url() }}" alt="{{ $house->house_name }}" loading="lazy" class="h-full w-full object-cover">
        @endif
        <div class="absolute left-3 top-3">
            <x-listings.kind-tag :mode="$house->listing_mode" />
        </div>
        @auth
            @if (auth()->user()->isUser())
                <form method="POST" action="{{ route('listings.watchlist', $house) }}" class="absolute right-3 top-3">
                    @csrf
                    <button type="submit" aria-label="{{ $watchlisted ? 'Remove from saved' : 'Save listing' }}" aria-pressed="{{ $watchlisted ? 'true' : 'false' }}"
                        class="grid h-9 w-9 place-items-center rounded-full bg-white/90 shadow-sm transition-colors dark:bg-slate-900/80 {{ $watchlisted ? 'text-rose-600' : 'text-slate-600 hover:text-rose-600 dark:text-slate-300 dark:hover:text-rose-400' }}">
                        @svg($watchlisted ? 'heroicon-s-heart' : 'heroicon-o-heart', 'w-4.5 h-4.5')
                    </button>
                </form>
            @endif
        @else
            <a href="{{ route('generic.login') }}" aria-label="Log in to save" class="absolute right-3 top-3 grid h-9 w-9 place-items-center rounded-full bg-white/90 text-slate-600 shadow-sm transition-colors hover:text-rose-600 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:text-rose-400">
                @svg('heroicon-o-heart', 'w-4.5 h-4.5')
            </a>
        @endauth
    </div>

    <div class="p-4">
        <div class="flex items-start justify-between gap-3">
            <p class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                @if ($isStay && $cheapestPackage)
                    KES {{ number_format($cheapestPackage->price) }}<span class="text-sm font-normal text-slate-500 dark:text-slate-400">/{{ $cheapestPackage->billing_unit }}</span>
                @else
                    KES {{ number_format($house->rent_amount) }}<span class="text-sm font-normal text-slate-500 dark:text-slate-400">/mo</span>
                @endif
            </p>
        </div>

        <h3 class="mt-1 truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $house->house_name }}</h3>

        <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400">
            @svg('heroicon-o-map-pin', 'w-4 h-4 shrink-0')
            <span class="truncate">{{ $house->location?->geo_id ?? $house->location?->location_name }}</span>
        </p>

        <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-400">
            @svg('heroicon-o-home-modern', 'w-4 h-4 shrink-0')
            {{ $house->house_type }}
        </p>

        <a href="{{ $showRoute }}" class="absolute inset-0" aria-label="View {{ $house->house_name }}"></a>
    </div>
</div>
