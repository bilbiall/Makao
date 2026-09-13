<div class="space-y-4">
    <div class="grid grid-cols-2 gap-2">
        <x-admin.stat-tile label="Revenue" value="KES {{ number_format($data['summary']['revenue']) }}" color="emerald" />
        <x-admin.stat-tile label="Bookings" value="{{ $data['summary']['bookings'] }}" color="sky" />
        <x-admin.stat-tile label="Occupancy" value="{{ $data['summary']['occupancy_rate'] }}%" color="amber" />
        <x-admin.stat-tile label="ADR (avg/night)" value="KES {{ number_format($data['summary']['adr']) }}" color="slate" />
    </div>

    <div class="grid grid-cols-3 gap-2">
        <x-admin.stat-tile label="Views" value="{{ $data['summary']['views'] }}" color="slate" />
        <x-admin.stat-tile label="Inquiries" value="{{ $data['summary']['inquiries'] }}" color="slate" />
        <x-admin.stat-tile label="Nights booked" value="{{ $data['summary']['nights'] }}" color="slate" />
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Revenue trend</p>
        <div class="mt-3" style="height: 180px">
            <canvas
                wire:key="bnb-chart-{{ md5(json_encode($data['labels'])) }}"
                x-data
                x-init="
                    const tickColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
                    new Chart($el, {
                        type: 'line',
                        data: {
                            labels: @js($data['labels']),
                            datasets: [{
                                data: @js($data['revenue_by_month']),
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                                fill: true,
                                tension: 0.35,
                                pointRadius: 3,
                                pointBackgroundColor: '#6366f1',
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

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">By property</p>
        </div>
        @forelse ($data['properties'] as $property)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 dark:border-slate-800/60 last:border-0">
                <div>
                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ $property['house_name'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $property['location_name'] }} &middot; {{ $property['views'] }} views &middot; {{ $property['inquiries'] }} inquiries</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($property['revenue']) }}</p>
                    <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $property['bookings'] }} booking(s)</p>
                </div>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400 text-center">No short-stay properties yet.</p>
        @endforelse
    </div>
</div>
