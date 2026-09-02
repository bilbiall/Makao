@props([
    'cities' => collect(),
    'counts' => [],
    'name' => 'area',
    'value' => '',
    'wireModel' => null,
    'placeholder' => 'Search a location...',
    'inputClass' => '',
])
@php
    // Alpine needs plain arrays/strings, not Eloquent models/collections. Each
    // area carries its own live count (0 if this area has no visible listings
    // right now); the city header carries the sum, so "Eldoret" alone (before
    // typing anything) already hints how much is actually there.
    $groups = $cities->map(function ($city) use ($counts) {
        $areas = $city->areas->map(fn ($area) => [
            'name' => $area->name,
            'count' => $counts[$area->name] ?? 0,
        ])->all();

        return [
            'city' => $city->name,
            'count' => array_sum(array_column($areas, 'count')),
            'areas' => $areas,
        ];
    })->all();

    // Two ways this field's value can live: a plain native <input name="...">
    // for the public GET-based search forms, or - when used inside a Livewire
    // component (wireModel set) - entangled straight to that component's
    // property, so typing/selecting here updates it exactly like wire:model
    // would, without Alpine's own x-model fighting Livewire for the DOM value.
    $queryInit = $wireModel
        ? "\$wire.entangle('{$wireModel}')"
        : \Illuminate\Support\Js::from($value ?? '');
@endphp
<div
    x-data="{
        query: {{ $queryInit }},
        open: false,
        groups: @js($groups),
        // Collapsed to just city names (no areas listed) until the visitor
        // actually types something - 100+ areas in one list is overwhelming;
        // typing a city or area name is what expands it.
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) {
                return this.groups.map((g) => ({ city: g.city, count: g.count, areas: [] }));
            }
            return this.groups
                .map((g) => {
                    const cityMatches = g.city.toLowerCase().includes(q);
                    return {
                        city: g.city,
                        count: g.count,
                        areas: cityMatches ? g.areas : g.areas.filter((a) => a.name.toLowerCase().includes(q)),
                    };
                })
                .filter((g) => g.areas.length > 0 || g.city.toLowerCase().includes(q));
        },
        select(value) {
            this.query = value;
            this.open = false;
        },
    }"
    @click.outside="open = false"
    @keydown.escape="open = false"
    class="relative"
>
    <input
        type="text"
        name="{{ $name }}"
        x-model="query"
        @focus="open = true"
        @input="open = true"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        {{ $attributes->merge(['class' => $inputClass]) }}
    >

    <div
        x-show="open && filtered.length > 0"
        x-cloak
        x-transition.opacity.duration.100ms
        class="absolute z-40 mt-1 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800"
    >
        <template x-for="group in filtered" :key="group.city">
            <div>
                <button
                    type="button"
                    @click="select(group.city)"
                    class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-left text-sm font-semibold text-slate-900 hover:bg-emerald-50 dark:text-slate-100 dark:hover:bg-emerald-500/10"
                >
                    <span x-text="group.city"></span>
                    <span class="flex shrink-0 items-center gap-2">
                        <span x-show="group.areas.length === 0" class="text-xs font-normal text-slate-400 dark:text-slate-500">All areas</span>
                        <template x-if="group.count > 0">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                <span class="h-1.5 w-1.5 shrink-0 animate-pulse rounded-full bg-emerald-500 shadow-[0_0_6px_2px_rgba(16,185,129,0.55)]"></span>
                                <span x-text="group.count"></span> available
                            </span>
                        </template>
                    </span>
                </button>
                <template x-for="area in group.areas" :key="area.name">
                    <button
                        type="button"
                        @click="select(area.name)"
                        class="flex w-full items-center justify-between gap-2 px-3 py-1.5 pl-6 text-left text-sm text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 dark:text-slate-300 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400"
                    >
                        <span x-text="area.name"></span>
                        <template x-if="area.count > 0">
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                <span class="h-1.5 w-1.5 shrink-0 animate-pulse rounded-full bg-emerald-500 shadow-[0_0_6px_2px_rgba(16,185,129,0.55)]"></span>
                                <span x-text="area.count"></span> available
                            </span>
                        </template>
                    </button>
                </template>
            </div>
        </template>
    </div>
</div>
