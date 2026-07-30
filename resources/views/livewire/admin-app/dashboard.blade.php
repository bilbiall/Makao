<div class="space-y-5">
    <div>
        <p class="text-sm text-slate-500">Welcome back,</p>
        <p class="text-xl font-bold text-slate-900">{{ auth()->user()->name }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
            <p class="text-xs text-emerald-700">Occupancy</p>
            <p class="mt-1 text-xl font-bold text-emerald-800">{{ $occupancyRate }}%</p>
            <p class="text-xs text-emerald-700 mt-0.5">{{ $occupiedHouses }}/{{ $totalHouses }} units</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4">
            <p class="text-xs text-amber-700">Revenue this month</p>
            <p class="mt-1 text-xl font-bold text-amber-800">KES {{ number_format($revenueThisMonth) }}</p>
        </div>
        <div class="rounded-2xl bg-rose-50 border border-rose-100 p-4">
            <p class="text-xs text-rose-700">Outstanding</p>
            <p class="mt-1 text-xl font-bold text-rose-800">KES {{ number_format($outstandingBalance) }}</p>
        </div>
        <div class="rounded-2xl bg-slate-100 border border-slate-200 p-4">
            <p class="text-xs text-slate-600">Tenants</p>
            <p class="mt-1 text-xl font-bold text-slate-800">{{ $totalTenants }}</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-900">Recent payments</p>
            <a href="{{ route('app.admin.payments') }}" class="text-xs font-medium text-emerald-700">See all</a>
        </div>
        @forelse ($recentPayments as $payment)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 last:border-0">
                <div>
                    <p class="font-medium text-slate-800">{{ $payment->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                    <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}</p>
                </div>
                <p class="font-semibold text-emerald-700">KES {{ number_format($payment->amount_paid) }}</p>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 text-center">No payments yet.</p>
        @endforelse
    </div>
</div>
