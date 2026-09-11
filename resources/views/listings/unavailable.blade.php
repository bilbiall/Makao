<x-layouts.marketing :title="$house->publicName()">
    <div class="pb-24 md:pb-14">
        <div class="mx-auto max-w-xl px-4 py-16 sm:px-6 text-center">
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm p-4 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                @svg('heroicon-o-home-modern', 'w-7 h-7 text-slate-400')
            </div>

            <h1 class="mt-5 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $house->publicName() }} is no longer available</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ $house->location?->geo_id ?? $house->location?->location_name }} &middot; {{ $house->house_type }}
                <br>
                This listing has either been taken or is no longer being advertised. You can ask to be notified the moment it opens up again.
            </p>

            <div class="mt-6">
                @auth
                    @if (! auth()->user()->isUser())
                        {{-- Alerts are a "looking for a house" account feature only. --}}
                    @elseif ($hasAlert)
                        <p class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
                            @svg('heroicon-o-check-circle', 'w-4 h-4')
                            You'll be emailed when it's available again
                        </p>
                    @else
                        <form method="POST" action="{{ route('listings.notify-me', $house) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                                Notify me when it's available again
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('generic.login') }}" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 inline-block">
                        Log in to get notified
                    </a>
                @endauth
            </div>

            <div class="mt-8 flex justify-center gap-4 text-sm">
                <a href="{{ route('listings.index') }}" class="text-emerald-700 dark:text-emerald-400 font-medium">Browse other listings</a>
                <span class="text-slate-300 dark:text-slate-700">&middot;</span>
                <a href="{{ route('app.user.alerts') }}" class="text-emerald-700 dark:text-emerald-400 font-medium">Set up a general alert</a>
            </div>
        </div>
    </div>
</x-layouts.marketing>
