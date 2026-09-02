@props([
    'cities' => collect(),
    'name' => 'area',
    'value' => '',
    'placeholder' => 'Search a location...',
    'inputClass' => '',
])
@php
    // Alpine needs plain arrays/strings, not Eloquent models/collections.
    $groups = $cities->map(fn ($city) => [
        'city' => $city->name,
        'areas' => $city->areas->pluck('name')->all(),
    ])->all();
@endphp
<div
    x-data="{
        query: @js($value ?? ''),
        open: false,
        groups: @js($groups),
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.groups;
            return this.groups
                .map((g) => {
                    const cityMatches = g.city.toLowerCase().includes(q);
                    return {
                        city: g.city,
                        cityMatches,
                        areas: cityMatches ? g.areas : g.areas.filter((a) => a.toLowerCase().includes(q)),
                    };
                })
                .filter((g) => g.cityMatches || g.areas.length > 0);
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
                    class="flex w-full items-center justify-between px-3 py-1.5 text-left text-sm font-semibold text-slate-900 hover:bg-emerald-50 dark:text-slate-100 dark:hover:bg-emerald-500/10"
                >
                    <span x-text="group.city"></span>
                    <span class="text-xs font-normal text-slate-400 dark:text-slate-500">All areas</span>
                </button>
                <template x-for="area in group.areas" :key="area">
                    <button
                        type="button"
                        @click="select(area)"
                        class="block w-full px-3 py-1.5 pl-6 text-left text-sm text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 dark:text-slate-300 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400"
                        x-text="area"
                    ></button>
                </template>
            </div>
        </template>
    </div>
</div>
