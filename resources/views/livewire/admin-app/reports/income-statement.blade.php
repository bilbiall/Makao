<div class="space-y-4">
    <div class="grid grid-cols-2 gap-2">
        <x-admin.stat-tile label="Total revenue" value="KES {{ number_format($data['totals']['total_revenue']) }}" color="emerald" />
        <x-admin.stat-tile label="Total expenses" value="KES {{ number_format($data['totals']['total_expense']) }}" color="rose" />
        <x-admin.stat-tile
            label="Net income"
            value="KES {{ number_format($data['totals']['net']) }}"
            :color="$data['totals']['net'] >= 0 ? 'emerald' : 'rose'"
            class="col-span-2"
        />
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Net income trend</p>
        <div class="mt-3" style="height: 180px">
            <canvas
                wire:key="income-chart-{{ md5(json_encode(array_column($data['rows'], 'month'))) }}"
                x-data
                x-init="
                    const tickColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
                    new Chart($el, {
                        type: 'bar',
                        data: {
                            labels: @js(array_column($data['rows'], 'month')),
                            datasets: [
                                { label: 'Revenue', data: @js(array_column($data['rows'], 'total_revenue')), backgroundColor: '#10b981' },
                                { label: 'Expenses', data: @js(array_column($data['rows'], 'total_expense')), backgroundColor: '#f43f5e' },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 10, font: { size: 10 } } } },
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

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">By month</p>
        </div>
        @forelse ($data['rows'] as $row)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 dark:border-slate-800/60 last:border-0">
                <div>
                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ $row['month'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Rent KES {{ number_format($row['rent_revenue']) }} &middot; BnB KES {{ number_format($row['bnb_revenue']) }}</p>
                </div>
                <div class="text-right">
                    <p @class([
                        'font-semibold',
                        'text-emerald-700 dark:text-emerald-400' => $row['net'] >= 0,
                        'text-rose-700 dark:text-rose-400' => $row['net'] < 0,
                    ])>KES {{ number_format($row['net']) }}</p>
                    <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Expenses KES {{ number_format($row['total_expense']) }}</p>
                </div>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400 text-center">No data in this period.</p>
        @endforelse
    </div>
</div>
