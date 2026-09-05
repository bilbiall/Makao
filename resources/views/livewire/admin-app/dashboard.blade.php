<div class="space-y-5">
    <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Welcome back,</p>
        <p class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4 dark:bg-emerald-500/10 dark:border-emerald-500/20">
            <p class="text-xs text-emerald-700 dark:text-emerald-400">Occupancy</p>
            <p class="mt-1 text-xl font-bold text-emerald-800 dark:text-emerald-300">{{ $occupancyRate }}%</p>
            <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5">{{ $occupiedHouses }}/{{ $totalHouses }} units</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4 dark:bg-amber-500/10 dark:border-amber-500/20">
            <p class="text-xs text-amber-700 dark:text-amber-400">Revenue this month</p>
            <p class="mt-1 text-xl font-bold text-amber-800 dark:text-amber-300">KES {{ number_format($revenueThisMonth) }}</p>
            @if ($revenueChangePercent !== 0)
                <p @class([
                    'text-xs mt-0.5 flex items-center gap-1',
                    'text-emerald-700 dark:text-emerald-400' => $revenueChangePercent > 0,
                    'text-rose-600 dark:text-rose-400' => $revenueChangePercent < 0,
                ])>
                    @svg($revenueChangePercent > 0 ? 'heroicon-s-arrow-trending-up' : 'heroicon-s-arrow-trending-down', 'w-3.5 h-3.5')
                    {{ abs($revenueChangePercent) }}% vs last month
                </p>
            @endif
        </div>
        <div class="rounded-2xl bg-rose-50 border border-rose-100 p-4 dark:bg-rose-500/10 dark:border-rose-500/20">
            <p class="text-xs text-rose-700 dark:text-rose-400">Outstanding</p>
            <p class="mt-1 text-xl font-bold text-rose-800 dark:text-rose-300">KES {{ number_format($outstandingBalance) }}</p>
        </div>
        <div class="rounded-2xl bg-slate-100 border border-slate-200 p-4 dark:bg-slate-800 dark:border-slate-700">
            <p class="text-xs text-slate-600 dark:text-slate-400">Tenants</p>
            <p class="mt-1 text-xl font-bold text-slate-800 dark:text-slate-200">{{ $totalTenants }}</p>
        </div>
    </div>

    @if ($pendingViewingRequestsCount > 0)
        <a href="{{ route('app.admin.viewing-requests') }}" class="block rounded-2xl bg-teal-50 border border-teal-100 p-4 dark:bg-teal-500/10 dark:border-teal-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-teal-700 dark:text-teal-400">Pending viewing requests</p>
                    <p class="mt-1 text-xl font-bold text-teal-800 dark:text-teal-300">{{ $pendingViewingRequestsCount }}</p>
                </div>
                @svg('heroicon-o-calendar-days', 'w-6 h-6 text-teal-600 dark:text-teal-400')
            </div>
        </a>
    @endif

    @if ($upcomingBookingsCount > 0 || $pendingBookingsCount > 0)
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('app.admin.bookings') }}" class="rounded-2xl bg-indigo-50 border border-indigo-100 p-4 dark:bg-indigo-500/10 dark:border-indigo-500/20">
                <p class="text-xs text-indigo-700 dark:text-indigo-400">Upcoming stays</p>
                <p class="mt-1 text-xl font-bold text-indigo-800 dark:text-indigo-300">{{ $upcomingBookingsCount }}</p>
            </a>
            <a href="{{ route('app.admin.bookings') }}" class="rounded-2xl bg-orange-50 border border-orange-100 p-4 dark:bg-orange-500/10 dark:border-orange-500/20">
                <p class="text-xs text-orange-700 dark:text-orange-400">Pending booking holds</p>
                <p class="mt-1 text-xl font-bold text-orange-800 dark:text-orange-300">{{ $pendingBookingsCount }}</p>
            </a>
        </div>
    @endif

    {{-- Revenue trend - real, computed data rendered via Chart.js (window.Chart, see
         resources/js/app.js) for a smooth animated line instead of plain CSS bars. --}}
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Revenue trend (6 months)</p>
        <div class="mt-3" style="height: 180px">
            <canvas
                x-data
                x-init="
                    const tickColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
                    new Chart($el, {
                        type: 'line',
                        data: {
                            labels: @js($revenueTrendLabels),
                            datasets: [{
                                data: @js($revenueTrendValues),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointBackgroundColor: '#10b981',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 1.5,
                            }],
                        },
                        options: {
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { color: tickColor } },
                                y: { beginAtZero: true, ticks: { color: tickColor, callback: (v) => 'KES ' + (v >= 1000 ? (v / 1000) + 'k' : v) } },
                            },
                        },
                    })
                "
            ></canvas>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        {{-- Occupancy donut - pure CSS conic-gradient, no chart library needed. --}}
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex flex-col items-center justify-center dark:bg-slate-900 dark:border-slate-800">
            <p class="self-start text-sm font-semibold text-slate-900 dark:text-slate-100">Occupancy</p>
            <div class="mt-2 relative h-24 w-24 rounded-full" style="background: conic-gradient(#10b981 {{ $occupancyRate * 3.6 }}deg, #e2e8f0 0deg);">
                <div class="absolute inset-2 rounded-full bg-white dark:bg-slate-900 flex items-center justify-center">
                    <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $occupancyRate }}%</span>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $occupiedHouses }} of {{ $totalHouses }} occupied</p>
        </div>

        {{-- Invoice status breakdown by invoiced amount - a second, genuinely
             different lens on collections health next to the "Outstanding" stat card. --}}
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex flex-col dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Invoice status</p>
            @if (count($invoiceStatusValues) > 0)
                <div class="mt-2 flex-1" style="min-height: 120px">
                    <canvas
                        x-data
                        x-init="
                            const legendColor = document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#334155';
                            new Chart($el, {
                                type: 'doughnut',
                                data: {
                                    labels: @js($invoiceStatusLabels),
                                    datasets: [{ data: @js($invoiceStatusValues), backgroundColor: @js($invoiceStatusColors), borderWidth: 0 }],
                                },
                                options: {
                                    maintainAspectRatio: false,
                                    plugins: { legend: { position: 'bottom', labels: { color: legendColor, boxWidth: 10, font: { size: 10 } } } },
                                },
                            })
                        "
                    ></canvas>
                </div>
            @else
                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400 text-center">No invoices yet.</p>
            @endif
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recent payments</p>
            <a href="{{ route('app.admin.payments') }}" class="text-xs font-medium text-emerald-700 dark:text-emerald-400">See all</a>
        </div>
        @forelse ($recentPayments as $payment)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 dark:border-slate-800/60 last:border-0">
                <div>
                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ $payment->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}</p>
                </div>
                <p class="font-semibold text-emerald-700 dark:text-emerald-400">KES {{ number_format($payment->amount_paid) }}</p>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400 text-center">No payments yet.</p>
        @endforelse
    </div>
</div>
