@php
    $cheapestPackage = $house->pricePackages->sortBy('price')->first();
    $shareDescription = trim(($house->house_type ?? '') . ' in ' . ($house->location?->geo_id ?? $house->location?->location_name ?? 'Kenya')
        . ($cheapestPackage ? ' - from KES ' . number_format($cheapestPackage->price) . '/' . $cheapestPackage->billing_unit : '')
        . ($house->reviewsCount() > 0 ? ' - ' . $house->averageRating() . '★ (' . $house->reviewsCount() . ' reviews)' : ''));
@endphp
<x-layouts.marketing :title="$house->publicName()" :description="$shareDescription" :image="$house->photos->first()?->url()">
    <div class="pb-14">
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm p-4 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Photo gallery --}}
            @if ($house->photos->isNotEmpty())
                <div class="grid grid-cols-4 gap-2 overflow-hidden rounded-2xl" style="grid-auto-rows: 9rem;">
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

            <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_360px]">
                <div class="min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <x-listings.kind-tag :mode="$house->listing_mode" />
                            @if ($house->location?->landlord?->isVerified())
                                <span title="This landlord has been reviewed and verified by our team" class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">
                                    @svg('heroicon-s-check-badge', 'w-3.5 h-3.5')
                                    Verified
                                </span>
                            @endif
                        </div>
                        <x-share-buttons :url="url()->current()" :text="'Check out ' . $house->publicName() . ' on Renty'" />
                    </div>
                    <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ $house->publicName() }}</h1>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400">
                        @svg('heroicon-o-map-pin', 'w-4 h-4 shrink-0')
                        {{ $house->location?->geo_id ?? $house->location?->location_name }} &middot; {{ $house->house_type }}
                        @if ($house->reviewsCount() > 0)
                            &middot;
                            <span class="flex items-center gap-1 text-slate-700 dark:text-slate-300 font-medium">
                                @svg('heroicon-s-star', 'w-4 h-4 text-amber-400')
                                {{ $house->averageRating() }} ({{ $house->reviewsCount() }})
                            </span>
                        @endif
                    </p>

                    @if ($agent)
                        <a href="{{ route('agents.show', $agent->ensureSlug()) }}" class="mt-4 flex items-center gap-3 rounded-xl border border-slate-200 p-3 hover:border-emerald-300 transition dark:border-slate-800 dark:hover:border-emerald-500/40">
                            @if ($agent->avatarUrl())
                                <img src="{{ $agent->avatarUrl() }}" class="h-10 w-10 rounded-full object-cover">
                            @else
                                <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                                    {{ strtoupper(substr($agent->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">Hosted by {{ $agent->name }}</p>
                                @if ($agent->averageHouseRating())
                                    <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                        @svg('heroicon-s-star', 'w-3.5 h-3.5 text-amber-400')
                                        {{ $agent->averageHouseRating() }} average across their stays
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endif

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

                    @if ($house->reviews->isNotEmpty())
                        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
                            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-slate-100">
                                @svg('heroicon-s-star', 'w-5 h-5 text-amber-400')
                                {{ $house->averageRating() }} &middot; {{ $house->reviewsCount() }} {{ $house->reviewsCount() === 1 ? 'review' : 'reviews' }}
                            </h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @foreach ($house->reviews as $review)
                                    <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                        <div class="flex items-center justify-between">
                                            <p class="flex items-center gap-0.5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @svg($i <= $review->rating ? 'heroicon-s-star' : 'heroicon-o-star', 'w-3.5 h-3.5 ' . ($i <= $review->rating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-700'))
                                                @endfor
                                            </p>
                                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ $review->created_at->diffForHumans() }}</span>
                                        </div>
                                        @if ($review->comment)
                                            <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $review->comment }}</p>
                                        @endif
                                        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">{{ $review->reviewerName() }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div>
                    <div class="lg:sticky lg:top-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="font-semibold text-slate-900 mb-4 dark:text-slate-100">Book your stay</h2>
                        <form method="POST" action="{{ route('bookings.store', $house) }}" class="space-y-4">
                            @csrf
                            @php $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'; @endphp

                            <div>
                                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Price package</label>
                                <select name="price_package_id" required class="{{ $inputClass }}">
                                    @foreach ($house->pricePackages as $package)
                                        <option value="{{ $package->id }}">{{ $package->name }} - KES {{ number_format($package->price) }} / {{ $package->billing_unit }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Check-in</label>
                                    <input type="date" name="check_in" required class="{{ $inputClass }}">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Check-out</label>
                                    <input type="date" name="check_out" required class="{{ $inputClass }}">
                                </div>
                            </div>

                            @auth
                                @if (auth()->user()->isUser())
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Booking as {{ auth()->user()->name }} - your details will be used automatically.</p>
                                    <input type="hidden" name="use_account" value="1">
                                @endif
                            @endauth

                            @if (!auth()->check() || !auth()->user()->isUser())
                                <div>
                                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Full name</label>
                                    <input type="text" name="guest_name" required class="{{ $inputClass }}">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Phone</label>
                                        <input type="text" name="guest_phone" required placeholder="2547XXXXXXXX" class="{{ $inputClass }}">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Email (optional)</label>
                                        <input type="email" name="guest_email" class="{{ $inputClass }}">
                                    </div>
                                </div>
                            @endif

                            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                                Request to book
                            </button>
                            <p class="text-xs text-slate-400 text-center dark:text-slate-500">Your dates are held for 20 minutes while you complete payment.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.marketing>
