<div class="space-y-4">
    <div class="grid grid-cols-2 gap-2">
        <x-admin.stat-tile label="Invoiced" value="KES {{ number_format($data['summary']['total_invoiced']) }}" color="sky" />
        <x-admin.stat-tile label="Paid" value="KES {{ number_format($data['summary']['total_paid']) }}" color="emerald" />
        <x-admin.stat-tile label="Outstanding" value="KES {{ number_format($data['summary']['outstanding']) }}" color="rose" />
        <x-admin.stat-tile label="Collection rate" value="{{ $data['summary']['collection_rate'] }}%" color="amber" />
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Invoiced vs Paid</p>
        <div class="mt-3" style="height: 200px">
            <canvas
                wire:key="overview-chart-{{ md5(json_encode($data['labels'])) }}"
                x-data
                x-init="
                    const tickColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
                    new Chart($el, {
                        type: 'bar',
                        data: {
                            labels: @js($data['labels']),
                            datasets: [
                                { label: 'Invoiced', data: @js($data['invoice_totals']), backgroundColor: '#0ea5e9' },
                                { label: 'Paid', data: @js($data['payment_totals']), backgroundColor: '#10b981' },
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
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                Invoices{{ $data['status_label'] ? " - {$data['status_label']}" : '' }}
            </p>
        </div>

        @forelse ($data['by_property'] as $property)
            <div class="border-b border-slate-100 last:border-0 dark:border-slate-800">
                <div class="flex items-center justify-between px-4 py-2 bg-slate-50 dark:bg-slate-800/60">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $property['location_name'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $property['invoices']->count() }} invoice(s)</p>
                </div>
                @foreach ($property['invoices'] as $invoice)
                    <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 dark:border-slate-800/60 last:border-0">
                        <div>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ $invoice->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $invoice->invoice_number }} &middot; {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($invoice->amount) }}</p>
                            <span @class([
                                'text-[11px] font-medium',
                                'text-emerald-600' => $invoice->status === 'paid',
                                'text-amber-600' => $invoice->status === 'partial',
                                'text-rose-600' => $invoice->status === 'unpaid',
                            ])>{{ ucfirst($invoice->status) }}</span>
                        </div>
                    </div>
                @endforeach
                <div class="flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400">
                    <span>Subtotal</span>
                    <span>KES {{ number_format($property['subtotal_invoiced']) }} &middot; Balance KES {{ number_format($property['subtotal_balance']) }}</span>
                </div>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400 text-center">No invoices in this period.</p>
        @endforelse
    </div>
</div>
