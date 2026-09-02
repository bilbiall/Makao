<x-layouts.marketing :title="$house->publicName()">
    <div class="pb-24 md:pb-14">
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm p-4 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm p-4 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Photo gallery --}}
            @if ($house->photos->isNotEmpty())
                <div class="grid grid-cols-4 gap-2 overflow-hidden rounded-2xl" style="grid-auto-rows: 9rem;" x-data>
                    <div class="col-span-4 row-span-2 sm:col-span-2 sm:row-span-2 bg-slate-100 dark:bg-slate-800">
                        <img src="{{ $house->photos->first()->url() }}" class="h-full w-full object-cover" alt="{{ $house->publicName() }}">
                    </div>
                    @foreach ($house->photos->skip(1)->take(4) as $photo)
                        <div class="hidden sm:block bg-slate-100 dark:bg-slate-800">
                            <img src="{{ $photo->url() }}" class="h-full w-full object-cover" alt="{{ $house->publicName() }}">
                        </div>
                    @endforeach
                </div>
            @else
                <div class="aspect-video rounded-2xl bg-slate-100 dark:bg-slate-800"></div>
            @endif

            <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_320px]">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <x-listings.kind-tag :mode="$house->listing_mode" />
                    </div>
                    <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ $house->publicName() }}</h1>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400">
                        @svg('heroicon-o-map-pin', 'w-4 h-4 shrink-0')
                        {{ $house->location?->geo_id ?? $house->location?->location_name }} &middot; {{ $house->house_type }}{{ $house->size_label ? ' · ' . $house->size_label : '' }}
                    </p>

                    @if ($house->description)
                        <p class="mt-6 text-slate-700 leading-relaxed dark:text-slate-300">{{ $house->description }}</p>
                    @endif

                    @if (!empty($house->amenities))
                        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">What this place offers</h2>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @foreach ($house->amenities as $amenity)
                                    <div class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        @svg('heroicon-o-check-circle', 'w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400')
                                        {{ $amenity }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <x-listings.nearby-places :house="$house" />
                </div>

                {{-- Action panel - sticky on desktop, fixed bottom bar on mobile --}}
                <div class="hidden lg:block">
                    <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">KES {{ number_format($house->rent_amount) }}<span class="text-sm font-normal text-slate-500 dark:text-slate-400">/mo</span></p>
                        @include('listings._actions')
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile sticky action bar --}}
        <div class="lg:hidden fixed inset-x-0 bottom-16 z-30 border-t border-slate-200 bg-white/95 backdrop-blur px-4 py-3 dark:border-slate-800 dark:bg-slate-900/95">
            <div class="flex items-center justify-between gap-3">
                <p class="text-lg font-bold text-slate-900 dark:text-slate-100">KES {{ number_format($house->rent_amount) }}<span class="text-xs font-normal text-slate-500 dark:text-slate-400">/mo</span></p>
                <div class="flex-1 max-w-[220px]">
                    @include('listings._actions', ['compact' => true])
                </div>
            </div>
        </div>
    </div>
</x-layouts.marketing>
