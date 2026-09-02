@props(['house'])
@php $nearby = $house->nearbyPlacesForDisplay(); @endphp
@if (!empty($nearby))
    <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Getting around</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($nearby as $place)
                <div class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    @svg('heroicon-o-map-pin', 'w-4 h-4 shrink-0 text-emerald-600 dark:text-emerald-400')
                    <span>{{ $place['label'] }} <span class="text-slate-400 dark:text-slate-500">&middot; {{ $place['minutes'] }} min</span></span>
                </div>
            @endforeach
        </div>
    </div>
@endif
