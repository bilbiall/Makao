<footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-600 text-sm font-bold text-white">R</span>
            <span class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">Renty</span>
        </div>
        <p class="mt-3 max-w-sm text-sm text-slate-500 dark:text-slate-400">
            Verified houses to rent and furnished stays across Kenya.
        </p>
        <div class="mt-6 grid gap-6 sm:grid-cols-3">
            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Tenants</h4>
                <ul class="mt-2 space-y-2">
                    <li><a href="{{ route('listings.index') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Rent Long-Term</a></li>
                    <li><a href="{{ route('stays.index') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Find a BnB</a></li>
                    <li><a href="{{ route('app.user.watchlist') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Saved listings</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Property Owners</h4>
                <ul class="mt-2 space-y-2">
                    <li><a href="{{ route('for-landlords') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">List a property</a></li>
                    <li><a href="{{ route('pricing') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Pricing</a></li>
                    <li><a href="{{ route('signup') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Create an account</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Renty</h4>
                <ul class="mt-2 space-y-2">
                    <li><a href="{{ route('generic.login') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Log in</a></li>
                    <li><a href="{{ route('get-started') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Get started</a></li>
                    <li><a href="{{ route('privacy') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Privacy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-sm text-slate-500 dark:text-slate-400 transition-colors hover:text-emerald-700 dark:hover:text-emerald-400">Terms</a></li>
                </ul>
            </div>
        </div>
        <p class="mt-8 border-t border-slate-200 dark:border-slate-800 pt-6 text-xs text-slate-500 dark:text-slate-400">
            &copy; {{ now()->year }} Renty. Nairobi, Kenya.
        </p>
    </div>
</footer>
