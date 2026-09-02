@props(['cities' => collect()])
@php
    // Two sizes in one set of classes rather than two markups: compact/unlabeled on
    // phones (own row per field would run this off the bottom of the hero photo),
    // full-size with visible labels from `lg:` up - see the labels/button below.
    $fieldClass = 'w-full min-w-0 rounded-md lg:rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs lg:px-3 lg:py-2.5 lg:text-sm text-slate-900 outline-none transition-colors focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20';
    $labelClass = 'mb-1 hidden text-xs font-medium text-slate-600 lg:block';
    $submitClass = 'inline-flex w-9 lg:w-auto shrink-0 items-center justify-center gap-2 rounded-md lg:rounded-lg bg-emerald-600 px-0 lg:px-6 py-1.5 lg:py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700';
@endphp
{{-- Semi-transparent + blurred (not flat bg-white) so this reads as sitting on the
     hero photo rather than clashing with it as an opaque block. More transparent
     (bg-white/45) than a typical card so more of the photo shows through - the
     backdrop-blur keeps the fields legible regardless. Always exactly two rows -
     tabs, then every field inline on one row - shrunk down (no labels, icon-only
     submit) below `lg:`, full-size with labels and text from `lg:` up. --}}
<div x-data="{ tab: 'home' }" class="rounded-2xl border border-white/40 bg-white/45 backdrop-blur-md p-1.5 lg:p-2 shadow-lg shadow-black/10 w-full sm:mx-auto sm:max-w-md lg:mx-0 lg:max-w-none">
    <div class="grid grid-cols-2 gap-1 rounded-xl bg-stone-100/80 p-1">
        <button type="button" @click="tab = 'home'" :class="tab === 'home' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-3 py-1.5 text-xs lg:px-4 lg:py-2 lg:text-sm font-semibold transition-colors">
            Rent Long-Term
        </button>
        <button type="button" @click="tab = 'stay'" :class="tab === 'stay' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="rounded-lg px-3 py-1.5 text-xs lg:px-4 lg:py-2 lg:text-sm font-semibold transition-colors">
            Find a BnB
        </button>
    </div>

    <form x-show="tab === 'home'" x-cloak method="GET" action="{{ route('listings.index') }}" class="grid grid-cols-[1.3fr_1fr_0.9fr_auto] items-end gap-1.5 p-1.5 lg:gap-3 lg:p-3 lg:grid-cols-[1.2fr_1fr_1fr_auto]">
        <div class="min-w-0">
            <label class="{{ $labelClass }}" for="h-area">Location</label>
            <x-area-search-input id="h-area" :cities="$cities" placeholder="Location" :input-class="$fieldClass" />
        </div>
        <div class="min-w-0">
            <label class="{{ $labelClass }}" for="h-type">Property type</label>
            <select id="h-type" name="house_type" class="{{ $fieldClass }}">
                <option value="">Any type</option>
                @foreach (['Bedsitter', 'Single Room', '1 Bedroom', '2 Bedroom', '3 Bedroom'] as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-0">
            <label class="{{ $labelClass }}" for="h-budget">Max budget (KES)</label>
            <input id="h-budget" name="max_rent" type="number" inputmode="numeric" min="0" step="1000" placeholder="Budget" class="{{ $fieldClass }}">
        </div>
        <button type="submit" class="{{ $submitClass }}">
            @svg('heroicon-o-magnifying-glass', 'w-4 h-4')
            <span class="hidden lg:inline">Search</span>
        </button>
    </form>

    <form x-show="tab === 'stay'" x-cloak method="GET" action="{{ route('stays.index') }}" class="grid grid-cols-[1.3fr_1fr_0.9fr_auto] items-end gap-1.5 p-1.5 lg:gap-3 lg:p-3 lg:grid-cols-[1.2fr_1fr_1fr_auto]">
        <div class="min-w-0">
            <label class="{{ $labelClass }}" for="s-area">Location</label>
            <x-area-search-input id="s-area" :cities="$cities" placeholder="Location" :input-class="$fieldClass" />
        </div>
        <div class="min-w-0">
            <label class="{{ $labelClass }}" for="s-checkin">Check-in</label>
            <input id="s-checkin" name="check_in" type="date" class="{{ $fieldClass }}">
        </div>
        <div class="min-w-0">
            <label class="{{ $labelClass }}" for="s-checkout">Check-out</label>
            <input id="s-checkout" name="check_out" type="date" class="{{ $fieldClass }}">
        </div>
        <button type="submit" class="{{ $submitClass }}">
            @svg('heroicon-o-magnifying-glass', 'w-4 h-4')
            <span class="hidden lg:inline">Search</span>
        </button>
    </form>

</div>
