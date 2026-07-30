<div class="space-y-5">
    <div>
        <p class="text-sm text-slate-500">Welcome back,</p>
        <p class="text-xl font-bold text-slate-900">{{ auth()->user()->name }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-slate-100 border border-slate-200 p-4">
            <p class="text-xs text-slate-600">Landlords</p>
            <p class="mt-1 text-xl font-bold text-slate-800">{{ $totalLandlords }}</p>
        </div>
        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
            <p class="text-xs text-emerald-700">Active subscriptions</p>
            <p class="mt-1 text-xl font-bold text-emerald-800">{{ $activeCount }}</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4">
            <p class="text-xs text-amber-700">Trialing</p>
            <p class="mt-1 text-xl font-bold text-amber-800">{{ $trialingCount }}</p>
        </div>
        <div class="rounded-2xl bg-rose-50 border border-rose-100 p-4">
            <p class="text-xs text-rose-700">Est. MRR</p>
            <p class="mt-1 text-xl font-bold text-rose-800">KES {{ number_format($mrr) }}</p>
        </div>
    </div>

    <a href="{{ route('app.superadmin.landlords') }}" class="block rounded-2xl bg-white border border-slate-200 shadow-sm p-4 text-sm font-medium text-emerald-700 text-center">
        View all landlords &rarr;
    </a>
</div>
