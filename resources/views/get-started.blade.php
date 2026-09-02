<x-layouts.marketing :title="'Get started'">
    <div class="max-w-4xl mx-auto px-6 py-20">
        <div class="text-center mb-12">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">What brings you here?</h1>
            <p class="mt-2 text-slate-500 dark:text-slate-400">Choose the account that matches what you want to do.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="{{ route('signup') }}" class="group rounded-2xl bg-white border border-slate-200 shadow-sm p-8 hover:border-emerald-400 hover:shadow-md transition dark:bg-slate-900 dark:border-slate-800 dark:hover:border-emerald-500/50">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mb-4 dark:bg-emerald-500/10">
                    <svg class="w-6 h-6 text-emerald-700 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M13 21V9l6 3v9M9 9h.01M9 13h.01M9 17h.01"/></svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">List and manage properties</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">You're a property owner or manager. Set up your account, add properties, and manage tenants, rent, and maintenance.</p>
                <span class="mt-4 inline-flex items-center text-sm font-semibold text-emerald-700 group-hover:underline dark:text-emerald-400">Get started as a property owner →</span>
            </a>

            <a href="{{ route('user-signup') }}" class="group rounded-2xl bg-white border border-slate-200 shadow-sm p-8 hover:border-emerald-400 hover:shadow-md transition dark:bg-slate-900 dark:border-slate-800 dark:hover:border-emerald-500/50">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center mb-4 dark:bg-amber-500/10">
                    <svg class="w-6 h-6 text-amber-700 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Looking for a house</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Browse verified vacant houses, save favourites, and request a viewing when you find one you like.</p>
                <span class="mt-4 inline-flex items-center text-sm font-semibold text-amber-700 group-hover:underline dark:text-amber-400">Get started as a renter →</span>
            </a>
        </div>

        <p class="mt-10 text-center text-sm text-slate-500 dark:text-slate-400">
            Just browsing? <a href="{{ route('listings.index') }}" class="text-emerald-700 font-medium hover:underline dark:text-emerald-400">See available houses</a> - no account needed.
        </p>
    </div>
</x-layouts.marketing>
