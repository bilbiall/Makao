<div class="space-y-5">
    <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Welcome back,</p>
        <p class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-slate-100 border border-slate-200 p-4 dark:bg-slate-800 dark:border-slate-700">
            <p class="text-xs text-slate-600 dark:text-slate-400">Landlords</p>
            <p class="mt-1 text-xl font-bold text-slate-800 dark:text-slate-200">{{ $totalLandlords }}</p>
        </div>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4 dark:bg-emerald-500/10 dark:border-emerald-500/20">
            <p class="text-xs text-emerald-700 dark:text-emerald-400">Active subscriptions</p>
            <p class="mt-1 text-xl font-bold text-emerald-800 dark:text-emerald-300">{{ $activeCount }}</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4 dark:bg-amber-500/10 dark:border-amber-500/20">
            <p class="text-xs text-amber-700 dark:text-amber-400">Trialing</p>
            <p class="mt-1 text-xl font-bold text-amber-800 dark:text-amber-300">{{ $trialingCount }}</p>
        </div>
        <div class="rounded-2xl bg-rose-50 border border-rose-100 p-4 dark:bg-rose-500/10 dark:border-rose-500/20">
            <p class="text-xs text-rose-700 dark:text-rose-400">Est. MRR</p>
            <p class="mt-1 text-xl font-bold text-rose-800 dark:text-rose-300">KES {{ number_format($mrr) }}</p>
        </div>
    </div>

    <a href="{{ route('app.superadmin.landlords') }}" class="block rounded-2xl bg-white border border-slate-200 shadow-sm p-4 text-sm font-medium text-emerald-700 dark:bg-slate-900 dark:border-slate-800 dark:text-emerald-400 text-center">
        View all landlords &rarr;
    </a>
</div>
