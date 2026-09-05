<div class="space-y-4">
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3 dark:bg-emerald-500/10 dark:border-emerald-500/20">
            <p class="text-[11px] text-emerald-700 dark:text-emerald-400">Invoiced</p>
            <p class="mt-1 text-sm font-bold text-emerald-800 dark:text-emerald-300">KES {{ number_format($totalInvoiced) }}</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-3 dark:bg-amber-500/10 dark:border-amber-500/20">
            <p class="text-[11px] text-amber-700 dark:text-amber-400">Paid</p>
            <p class="mt-1 text-sm font-bold text-amber-800 dark:text-amber-300">KES {{ number_format($totalPaid) }}</p>
        </div>
        <div class="rounded-2xl bg-rose-50 border border-rose-100 p-3 dark:bg-rose-500/10 dark:border-rose-500/20">
            <p class="text-[11px] text-rose-700 dark:text-rose-400">Outstanding</p>
            <p class="mt-1 text-sm font-bold text-rose-800 dark:text-rose-300">KES {{ number_format($totalOutstanding) }}</p>
        </div>
    </div>

    <div class="flex gap-2 text-xs font-medium">
        <span class="rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 dark:bg-emerald-500/10 dark:text-emerald-400">{{ $paidCount }} paid</span>
        <span class="rounded-full bg-amber-100 text-amber-700 px-2.5 py-1 dark:bg-amber-500/10 dark:text-amber-400">{{ $partialCount }} partial</span>
        <span class="rounded-full bg-rose-100 text-rose-700 px-2.5 py-1 dark:bg-rose-500/10 dark:text-rose-400">{{ $unpaidCount }} unpaid</span>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Invoiced vs paid (6 months)</p>
        <div class="mt-3" style="height: 160px">
            <canvas
                x-data
                x-init="
                    const tickColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
                    new Chart($el, {
                        type: 'line',
                        data: {
                            labels: @js($trendLabels),
                            datasets: [
                                { label: 'Invoiced', data: @js($trendInvoiced), borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.12)', fill: true, tension: 0.35, pointRadius: 2 },
                                { label: 'Paid', data: @js($trendPaid), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.12)', fill: true, tension: 0.35, pointRadius: 2 },
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

    <div class="flex gap-2">
        <select wire:model.live="statusFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <option value="">All statuses</option>
            <option value="paid">Paid</option>
            <option value="partial">Partial</option>
            <option value="unpaid">Unpaid</option>
        </select>
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search invoice # or tenant"
            class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder:text-slate-400">
        <a href="{{ route('app.admin.invoices.print', ['status' => $statusFilter, 'search' => $search]) }}" target="_blank"
            class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Print">
            @svg('heroicon-o-printer', 'w-5 h-5')
        </a>
    </div>

    @forelse ($invoices as $invoice)
        <button type="button" wire:click="viewInvoice({{ $invoice->id }})"
            class="w-full text-left rounded-2xl bg-white border border-slate-200 shadow-sm p-4 hover:border-emerald-300 hover:bg-emerald-50/40 transition dark:bg-slate-900 dark:border-slate-800 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/10">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $invoice->invoice_number }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $invoice->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-emerald-100 text-emerald-700' => $invoice->status === 'paid',
                    'bg-amber-100 text-amber-700' => $invoice->status === 'partial',
                    'bg-rose-100 text-rose-700' => $invoice->status === 'unpaid',
                ])>{{ ucfirst($invoice->status) }}</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-slate-500 dark:text-slate-400 text-xs">Amount</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($invoice->amount) }}</p>
                </div>
                <div>
                    <p class="text-slate-500 dark:text-slate-400 text-xs">Balance</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($invoice->balance) }}</p>
                </div>
            </div>
        </button>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No invoices found.
        </div>
    @endforelse

    <div>{{ $invoices->links() }}</div>

    {{-- Invoice detail popup --}}
    @if ($this->selectedInvoice)
        @php $inv = $this->selectedInvoice; @endphp
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-900/40" wire:click="closeInvoiceModal"></div>
            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
                <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-lg max-h-[85vh] sm:max-h-[80vh] overflow-y-auto shadow-xl dark:bg-slate-900">
                    <div class="sticky top-0 bg-white border-b border-slate-200 p-4 flex items-start justify-between dark:bg-slate-900 dark:border-slate-800">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $inv->invoice_number }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $inv->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">Due {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}</p>
                        </div>
                        <button type="button" wire:click="closeInvoiceModal" class="p-1 text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-300 flex-shrink-0" aria-label="Close">
                            @svg('heroicon-o-x-mark', 'w-5 h-5')
                        </button>
                    </div>

                    <div class="p-4 grid grid-cols-3 gap-3">
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Amount</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">KES {{ number_format($inv->amount) }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Balance</p>
                            <p class="text-sm font-semibold {{ $inv->balance > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400' }}">KES {{ number_format($inv->balance) }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Status</p>
                            <p @class([
                                'text-sm font-semibold',
                                'text-emerald-700 dark:text-emerald-400' => $inv->status === 'paid',
                                'text-amber-600 dark:text-amber-400' => $inv->status === 'partial',
                                'text-rose-600 dark:text-rose-400' => $inv->status === 'unpaid',
                            ])>{{ ucfirst($inv->status) }}</p>
                        </div>
                    </div>

                    <div class="px-4 pb-4">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Payment history</p>
                        <div class="space-y-2">
                            @forelse ($inv->payments as $payment)
                                <div class="flex items-center justify-between rounded-lg border border-slate-100 dark:border-slate-800 px-3 py-2">
                                    <div>
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ ucfirst($payment->payment_method ?? 'Payment') }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '' }}{{ $payment->payment_reference ? ' · ' . $payment->payment_reference : '' }}
                                        </p>
                                    </div>
                                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">KES {{ number_format($payment->amount_paid) }}</p>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 dark:text-slate-500 py-2">No payments recorded against this invoice yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
