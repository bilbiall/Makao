<x-layouts.marketing :title="$house->house_name">
    <div class="pb-14">
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm p-4">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Photo gallery --}}
            @if ($house->photos->isNotEmpty())
                <div class="grid grid-cols-4 gap-2 overflow-hidden rounded-2xl" style="grid-auto-rows: 9rem;">
                    <div class="col-span-4 row-span-2 sm:col-span-2 sm:row-span-2 bg-slate-100">
                        <img src="{{ $house->photos->first()->url() }}" class="h-full w-full object-cover" alt="{{ $house->house_name }}">
                    </div>
                    @foreach ($house->photos->skip(1)->take(4) as $photo)
                        <div class="hidden sm:block bg-slate-100">
                            <img src="{{ $photo->url() }}" class="h-full w-full object-cover" alt="{{ $house->house_name }}">
                        </div>
                    @endforeach
                </div>
            @else
                <div class="aspect-video rounded-2xl bg-slate-100"></div>
            @endif

            <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_360px]">
                <div class="min-w-0">
                    <x-listings.kind-tag :mode="$house->listing_mode" />
                    <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $house->house_name }}</h1>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                        @svg('heroicon-o-map-pin', 'w-4 h-4 shrink-0')
                        {{ $house->location?->geo_id ?? $house->location?->location_name }} &middot; {{ $house->house_type }}
                    </p>

                    @if ($house->description)
                        <p class="mt-6 text-slate-700 leading-relaxed">{{ $house->description }}</p>
                    @endif

                    @if (!empty($house->amenities))
                        <div class="mt-8 border-t border-slate-200 pt-6">
                            <h2 class="text-base font-semibold text-slate-900">What this place offers</h2>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @foreach ($house->amenities as $amenity)
                                    <div class="flex items-center gap-2 text-sm text-slate-700">
                                        @svg('heroicon-o-check-circle', 'w-5 h-5 shrink-0 text-emerald-600')
                                        {{ $amenity }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div>
                    <div class="lg:sticky lg:top-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="font-semibold text-slate-900 mb-4">Book your stay</h2>
                        <form method="POST" action="{{ route('bookings.store', $house) }}" class="space-y-4">
                            @csrf

                            <div>
                                <label class="text-xs font-medium text-slate-600">Price package</label>
                                <select name="price_package_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                                    @foreach ($house->pricePackages as $package)
                                        <option value="{{ $package->id }}">{{ $package->name }} - KES {{ number_format($package->price) }} / {{ $package->billing_unit }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-slate-600">Check-in</label>
                                    <input type="date" name="check_in" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-slate-600">Check-out</label>
                                    <input type="date" name="check_out" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                                </div>
                            </div>

                            @auth
                                @if (auth()->user()->isUser())
                                    <p class="text-xs text-slate-500">Booking as {{ auth()->user()->name }} - your details will be used automatically.</p>
                                    <input type="hidden" name="use_account" value="1">
                                @endif
                            @endauth

                            @if (!auth()->check() || !auth()->user()->isUser())
                                <div>
                                    <label class="text-xs font-medium text-slate-600">Full name</label>
                                    <input type="text" name="guest_name" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-medium text-slate-600">Phone</label>
                                        <input type="text" name="guest_phone" required placeholder="2547XXXXXXXX" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-slate-600">Email (optional)</label>
                                        <input type="email" name="guest_email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                                    </div>
                                </div>
                            @endif

                            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                                Request to book
                            </button>
                            <p class="text-xs text-slate-400 text-center">Your dates are held for 20 minutes while you complete payment.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.marketing>
