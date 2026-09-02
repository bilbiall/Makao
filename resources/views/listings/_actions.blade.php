@php $compact = $compact ?? false; @endphp
<div class="{{ $compact ? 'flex gap-2' : 'mt-4 space-y-2' }}">
    @auth
        @if (auth()->user()->isUser())
            <form method="POST" action="{{ route('listings.watchlist', $house) }}">
                @csrf
                <button type="submit" @class([
                    'w-full rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800',
                    'px-4 py-2.5' => $compact,
                    'px-6 py-3' => !$compact,
                ])>
                    {{ $isWatchlisted ? 'Saved' : 'Save' }}
                </button>
            </form>
            <form method="POST" action="{{ route('listings.request-viewing', $house) }}">
                @csrf
                <button type="submit" @disabled($pendingRequest) @class([
                    'w-full rounded-lg bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed',
                    'px-4 py-2.5' => $compact,
                    'px-6 py-3' => !$compact,
                ])>
                    {{ $pendingRequest ? 'Requested' : 'Request a viewing' }}
                </button>
            </form>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400">Only "looking for a house" accounts can save listings or request viewings.</p>
        @endif
    @else
        <a href="{{ route('user-signup') }}" @class([
            'block text-center rounded-lg bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-700 transition',
            'px-4 py-2.5' => $compact,
            'px-6 py-3' => !$compact,
        ])>
            {{ $compact ? 'Sign up to request' : 'Sign up to request a viewing' }}
        </a>
        @if (!$compact)
            <a href="{{ route('generic.login') }}" class="block text-center rounded-lg border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                Log in
            </a>
        @endif
    @endauth
</div>
