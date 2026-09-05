<div class="space-y-4">
    @if (session('payment-recorded'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('payment-recorded') }}
        </div>
    @endif

    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3 dark:bg-emerald-500/10 dark:border-emerald-500/20">
            <p class="text-[11px] text-emerald-700 dark:text-emerald-400">This month</p>
            <p class="mt-1 text-sm font-bold text-emerald-800 dark:text-emerald-300">KES {{ number_format($collectedThisMonth) }}</p>
        </div>
        <div class="rounded-2xl bg-slate-100 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
            <p class="text-[11px] text-slate-600 dark:text-slate-400">Payments</p>
            <p class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-200">{{ $paymentCount }}</p>
        </div>
        <div class="rounded-2xl bg-slate-100 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
            <p class="text-[11px] text-slate-600 dark:text-slate-400">Average</p>
            <p class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-200">KES {{ number_format($averagePayment) }}</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Collections (6 months)</p>
        <div class="mt-3" style="height: 160px">
            <canvas
                x-data
                x-init="
                    const tickColor = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
                    new Chart($el, {
                        type: 'line',
                        data: {
                            labels: @js($trendLabels),
                            datasets: [{
                                data: @js($trendValues),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16,185,129,0.15)',
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

    <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
        + Record a payment
    </button>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Tenant</label>
                <select wire:model.live="tenant_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Select tenant</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->tenant_name }}</option>
                    @endforeach
                </select>
                @error('tenant_id') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Invoice</label>
                <select wire:model="invoice_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Select invoice</option>
                    @foreach ($invoiceOptions as $invoice)
                        <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - Balance KES {{ number_format($invoice->balance) }}</option>
                    @endforeach
                </select>
                @error('invoice_id') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Amount paid (KES)</label>
                <input type="number" wire:model="amount_paid" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('amount_paid') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Payment method</label>
                <select wire:model="payment_method" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank transfer</option>
                    <option value="mpesa">M-Pesa (manual entry)</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Reference</label>
                <input type="text" wire:model="payment_reference" placeholder="e.g. till slip number, M-Pesa code" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('payment_reference') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Payment date</label>
                <input type="date" wire:model="payment_date" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="record" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save payment</button>
            </div>
        </div>
    @endif

    <div class="flex gap-2">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search tenant or reference"
            class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder:text-slate-400">
        <a href="{{ route('app.admin.payments.print', ['search' => $search]) }}" target="_blank"
            class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Print">
            @svg('heroicon-o-printer', 'w-5 h-5')
        </a>
    </div>

    <div class="space-y-3">
        @forelse ($payments as $payment)
            <button type="button" wire:click="viewPayment({{ $payment->id }})"
                class="w-full text-left rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between hover:border-emerald-300 hover:bg-emerald-50/40 transition dark:bg-slate-900 dark:border-slate-800 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/10">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $payment->tenant?->tenant_name ?? 'Unknown' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ $payment->invoice?->invoice_number }} &middot;
                        {{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}
                    </p>
                </div>
                <p class="font-semibold text-emerald-700 dark:text-emerald-400">KES {{ number_format($payment->amount_paid) }}</p>
            </button>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No payments recorded yet.
            </div>
        @endforelse

        <div>{{ $payments->links() }}</div>
    </div>

    {{-- Payment detail popup --}}
    @if ($this->selectedPayment)
        @php $p = $this->selectedPayment; @endphp
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-900/40" wire:click="closePaymentModal"></div>
            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
                <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-lg max-h-[85vh] sm:max-h-[80vh] overflow-y-auto shadow-xl dark:bg-slate-900">
                    <div class="sticky top-0 bg-white border-b border-slate-200 p-4 flex items-start justify-between dark:bg-slate-900 dark:border-slate-800">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $p->tenant?->tenant_name ?? 'Unknown' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $p->invoice?->invoice_number }}</p>
                        </div>
                        <button type="button" wire:click="closePaymentModal" class="p-1 text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-300 flex-shrink-0" aria-label="Close">
                            @svg('heroicon-o-x-mark', 'w-5 h-5')
                        </button>
                    </div>

                    <div class="p-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Amount paid</p>
                            <p class="text-lg font-semibold text-emerald-700 dark:text-emerald-400">KES {{ number_format($p->amount_paid) }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Date</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ \Carbon\Carbon::parse($p->payment_date ?? $p->created_at)->format('d M Y') }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Method</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ucfirst($p->payment_method ?? '-') }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 dark:bg-slate-800 dark:border-slate-700">
                            <p class="text-xs text-slate-500 dark:text-slate-400">Reference</p>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $p->payment_reference ?: '-' }}</p>
                        </div>
                    </div>

                    @if ($p->invoice)
                        <div class="px-4 pb-4">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Invoice</p>
                            <div class="flex items-center justify-between rounded-lg border border-slate-100 dark:border-slate-800 px-3 py-2">
                                <div>
                                    <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $p->invoice->invoice_number }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Amount KES {{ number_format($p->invoice->amount) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Balance KES {{ number_format($p->invoice->balance) }}</p>
                                    <p @class([
                                        'text-xs font-medium',
                                        'text-emerald-600 dark:text-emerald-400' => $p->invoice->status === 'paid',
                                        'text-amber-600 dark:text-amber-400' => $p->invoice->status === 'partial',
                                        'text-rose-600 dark:text-rose-400' => $p->invoice->status === 'unpaid',
                                    ])>{{ ucfirst($p->invoice->status) }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
