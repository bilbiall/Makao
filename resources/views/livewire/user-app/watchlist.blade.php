<div class="space-y-3">
    @forelse ($houses as $house)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden flex dark:bg-slate-900 dark:border-slate-800">
            <div class="w-24 h-24 bg-slate-100 dark:bg-slate-800 flex-shrink-0">
                @if ($house->photos->first())
                    <img src="{{ $house->photos->first()->url() }}" class="w-full h-full object-cover" alt="{{ $house->house_name }}">
                @endif
            </div>
            <div class="flex-1 p-3 flex items-center justify-between">
                <div>
                    <a href="{{ route('listings.show', $house) }}" class="font-semibold text-slate-900 dark:text-slate-100 hover:underline">{{ $house->house_name }}</a>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $house->location?->location_name }}</p>
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400 mt-1">KES {{ number_format($house->rent_amount) }}/mo</p>
                </div>
                <button wire:click="unwatch({{ $house->id }})" class="text-xs font-medium text-rose-600 hover:underline">Remove</button>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No houses in your watchlist yet. <a href="{{ route('listings.index') }}" class="text-emerald-700 dark:text-emerald-400 font-medium">Browse listings →</a>
        </div>
    @endforelse

    {{ $houses->links() }}
</div>
