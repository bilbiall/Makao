<x-layouts.marketing :title="$user->name . ' - Renty Agent'">
    <div class="pb-24 md:pb-14">
        {{-- Hero --}}
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 dark:from-emerald-800 dark:to-slate-900">
            <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 text-center text-white">
                @if ($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" class="mx-auto h-24 w-24 rounded-full object-cover ring-4 ring-white/30" alt="{{ $user->name }}">
                @else
                    <div class="mx-auto h-24 w-24 rounded-full bg-white/20 flex items-center justify-center text-3xl font-semibold ring-4 ring-white/30">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif

                <h1 class="mt-4 text-2xl font-semibold tracking-tight">{{ $user->name }}</h1>

                <p class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium">
                    @svg('heroicon-s-check-badge', 'w-4 h-4')
                    Renty Agent
                </p>

                <div class="mt-3 flex items-center justify-center gap-4 text-sm">
                    @if ($averageRating)
                        <span class="flex items-center gap-1 font-semibold">
                            @svg('heroicon-s-star', 'w-4 h-4 text-amber-300')
                            {{ $averageRating }}
                            <span class="font-normal text-emerald-100">({{ $reviewsCount }} {{ $reviewsCount === 1 ? 'review' : 'reviews' }})</span>
                        </span>
                    @endif
                    <span class="flex items-center gap-1 text-emerald-100">
                        @svg('heroicon-o-home-modern', 'w-4 h-4')
                        {{ $houses->count() }} {{ $houses->count() === 1 ? 'stay' : 'stays' }}
                    </span>
                </div>

                @if ($user->bio)
                    <p class="mt-4 max-w-xl mx-auto text-sm text-emerald-50 leading-relaxed">{{ $user->bio }}</p>
                @endif
            </div>
        </div>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 space-y-10">
            {{-- Properties --}}
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Stays managed by {{ $user->name }}</h2>

                @if ($houses->isNotEmpty())
                    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($houses as $house)
                            <x-listings.card :house="$house" />
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">No stays listed yet - check back soon.</p>
                @endif
            </div>

            {{-- Reviews --}}
            @if ($reviews->isNotEmpty())
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">What guests say</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($reviews as $review)
                            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                                <div class="flex items-center justify-between">
                                    <p class="flex items-center gap-0.5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @svg($i <= $review->rating ? 'heroicon-s-star' : 'heroicon-o-star', 'w-4 h-4 ' . ($i <= $review->rating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-700'))
                                        @endfor
                                    </p>
                                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                                @if ($review->comment)
                                    <p class="mt-2 text-sm text-slate-700 dark:text-slate-300">{{ $review->comment }}</p>
                                @endif
                                <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">{{ $review->reviewerName() }} &middot; {{ $review->house?->publicName() }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.marketing>
