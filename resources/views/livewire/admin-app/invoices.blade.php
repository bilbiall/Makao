<div class="space-y-4">
    @if (session('invoice-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('invoice-saved') }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-2">
        <x-admin.stat-tile label="Invoiced" value="KES {{ number_format($totalInvoiced) }}" color="emerald" />
        <x-admin.stat-tile label="Paid" value="KES {{ number_format($totalPaid) }}" color="amber" />
        <x-admin.stat-tile label="Outstanding" value="KES {{ number_format($totalOutstanding) }}" color="rose" class="col-span-2" />
    </div>

    <div class="grid grid-cols-2 gap-2">
        @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::CREATE_INVOICES))
            <button type="button" wire:click="startCreate" class="rounded-xl bg-emerald-600 text-white text-sm font-semibold py-2.5 hover:bg-emerald-700 transition">
                + New invoice
            </button>
        @endif
        @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::SEND_MASS_INVOICES) || auth()->user()->hasPermission(\App\Support\StaffPermissions::SEND_MASS_REMINDERS))
            <div class="grid grid-cols-2 gap-2">
                @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::SEND_MASS_INVOICES))
                    <button type="button" wire:click="sendMassInvoices" wire:confirm="Generate this month's invoices for every tenant who doesn't have one yet?"
                        class="rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800">
                        Mass invoices
                    </button>
                @endif
                @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::SEND_MASS_REMINDERS))
                    <button type="button" wire:click="sendMassReminders" wire:confirm="Send an SMS reminder to every tenant with an outstanding balance?"
                        class="rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800">
                        Mass reminders
                    </button>
                @endif
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit invoice' : 'New invoice' }}</p>

            @if (!$editingId)
                <div class="relative"
                    x-data="{
                        open: false,
                        query: '{{ $tenant_id ? addslashes(optional($tenants->firstWhere('id', (int) $tenant_id))->tenant_name) : '' }}',
                        tenants: {{ $tenants->map(fn ($t) => ['id' => $t->id, 'name' => $t->tenant_name])->values()->toJson() }},
                        get filtered() {
                            const q = this.query.toLowerCase().trim();
                            return q === '' ? this.tenants : this.tenants.filter(t => t.name.toLowerCase().includes(q));
                        },
                        select(t) {
                            this.query = t.name;
                            this.open = false;
                            $wire.set('tenant_id', t.id);
                        },
                    }">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Tenant</label>
                    <input type="text" x-model="query" @focus="open = true" @input="open = true; if ($event.target.value === '') $wire.set('tenant_id', '')"
                        placeholder="Search tenant by name..." autocomplete="off"
                        class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <div x-show="open" @click.outside="open = false" style="display: none;"
                        class="absolute z-10 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                        <template x-for="t in filtered" :key="t.id">
                            <button type="button" @click="select(t)"
                                class="block w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-700 dark:text-slate-100" x-text="t.name"></button>
                        </template>
                        <p x-show="filtered.length === 0" class="px-3 py-2 text-xs text-slate-400">No tenant matches</p>
                    </div>
                    @error('tenant_id') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                @if ($tenant_id)
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Rent &amp; bills below are for <span class="font-medium text-slate-700 dark:text-slate-300">{{ $this->invoicePeriodLabel }}</span> - change the invoice date to bill a different month.
                    </p>
                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($rent_only) }}</p>
                            <p class="text-slate-500 dark:text-slate-400">Rent</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($bill_only) }}</p>
                            <p class="text-slate-500 dark:text-slate-400">Bills</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($previous_balance) }}</p>
                            <p class="text-slate-500 dark:text-slate-400">Prev. balance</p>
                        </div>
                    </div>
                @endif
            @endif

            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Amount (KES)</label>
                <input type="number" step="0.01" wire:model="amount" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('amount') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Invoice date</label>
                    <input type="date" wire:model.live="invoice_date" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Due date</label>
                    <input type="date" wire:model="due_date" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Comment (optional)</label>
                <textarea wire:model="comment" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" wire:click="cancelForm" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button type="button" wire:click="save" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
            </div>
        </div>
    @endif

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
    </div>
    <div class="flex gap-2">
        <input type="month" wire:model.live="monthFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
        <select wire:model.live="locationFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <option value="">All properties</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}">{{ $location->location_name }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="export" class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
            @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
        </button>
        <a href="{{ route('app.admin.invoices.print', ['status' => $statusFilter, 'search' => $search]) }}" target="_blank"
            class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Print">
            @svg('heroicon-o-printer', 'w-5 h-5')
        </a>
    </div>

    @forelse ($invoices as $invoice)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 hover:border-emerald-300 transition dark:bg-slate-900 dark:border-slate-800 dark:hover:border-emerald-500/40">
            <button type="button" wire:click="viewInvoice({{ $invoice->id }})" class="w-full text-left">
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
            @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::EDIT_INVOICES) || auth()->user()->hasPermission(\App\Support\StaffPermissions::DELETE_INVOICES))
                <div class="mt-3 flex gap-2">
                    @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::EDIT_INVOICES))
                        <button type="button" wire:click="startEdit({{ $invoice->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                            Edit
                        </button>
                    @endif
                    @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::DELETE_INVOICES))
                        <button type="button" wire:click="delete({{ $invoice->id }})" wire:confirm="Delete this invoice? This can't be undone." class="flex-1 rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                            Delete
                        </button>
                    @endif
                </div>
            @endif
        </div>
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
