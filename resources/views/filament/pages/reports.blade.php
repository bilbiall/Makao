<div>
<x-filament::page>
    <div class="space-y-6 w-full">
        {{-- Filters Section --}}
        <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-4">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-sm text-gray-500 dark:text-gray-300 font-medium mb-1">From</label>
                        <input type="month" name="from" value="{{ request()->query('from') ? \Carbon\Carbon::parse(request()->query('from'))->format('Y-m') : \Carbon\Carbon::parse($from)->format('Y-m') }}" class="mt-1 block w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-2 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-500 dark:text-gray-300 font-medium mb-1">To</label>
                        <input type="month" name="to" value="{{ request()->query('to') ? \Carbon\Carbon::parse(request()->query('to'))->format('Y-m') : \Carbon\Carbon::parse($to)->format('Y-m') }}" class="mt-1 block w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-2 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-500 dark:text-gray-300 font-medium mb-1">Tenant</label>
                        <input type="search" name="tenant_search" placeholder="Name or phone" value="{{ request()->query('tenant_search', $tenant_search ?? '') }}" class="mt-1 block w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-2 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-500 dark:text-gray-300 font-medium mb-1">Invoice Status</label>
                        <select name="invoice_status" class="mt-1 block w-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded px-2 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Invoices</option>
                            <option value="overdue" @if(request()->query('invoice_status') == 'overdue') selected @endif>Overdue</option>
                            <option value="due" @if(request()->query('invoice_status') == 'due') selected @endif>Due Today</option>
                            <option value="upcoming" @if(request()->query('invoice_status') == 'upcoming') selected @endif>Upcoming</option>
                            <option value="paid" @if(request()->query('invoice_status') == 'paid') selected @endif>Paid</option>
                            <option value="partial" @if(request()->query('invoice_status') == 'partial') selected @endif>Partial</option>
                            <option value="unpaid" @if(request()->query('invoice_status') == 'unpaid') selected @endif>Unpaid</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 shadow-sm hover:brightness-95 rounded border border-transparent focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500 bg-blue-600 text-white font-medium">
                            Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow rounded-lg">
                <p class="text-sm font-medium text-blue-100">Total Invoiced</p>
                <p class="text-2xl font-bold mt-1">KES {{ number_format($summary['total_invoiced'] ?? 0) }}</p>
            </div>
            <div class="p-4 bg-gradient-to-br from-green-500 to-green-600 text-white shadow rounded-lg">
                <p class="text-sm font-medium text-green-100">Total Paid</p>
                <p class="text-2xl font-bold mt-1">KES {{ number_format($summary['total_paid'] ?? 0) }}</p>
            </div>
            <div class="p-4 bg-gradient-to-br from-red-500 to-red-600 text-white shadow rounded-lg">
                <p class="text-sm font-medium text-red-100">Outstanding</p>
                <p class="text-2xl font-bold mt-1">KES {{ number_format($summary['outstanding'] ?? 0) }}</p>
            </div>
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-4">
                <h3 class="font-semibold mb-3 dark:text-gray-100 text-gray-900">Invoices vs Payments</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="chartInvoices"></canvas>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-4">
                <h3 class="font-semibold mb-3 dark:text-gray-100 text-gray-900">Payment Trend</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="chartPayments"></canvas>
                </div>
            </div>
        </div>

        {{-- Invoices Table with Export --}}
        <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-semibold dark:text-gray-100 text-gray-900 text-lg">
                        Invoice Records
                        @if($invoice_status_label)
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $invoice_status_label }})</span>
                        @endif
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total: {{ count($invoices) }} invoice(s)</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    {{-- Export buttons disabled for now - uncomment when PDF/Excel libraries are installed --}}
                    {{-- <button onclick="exportToPDF()" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg shadow-sm text-sm font-medium transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Export PDF
                    </button>
                    <button onclick="exportToExcel()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-sm text-sm font-medium transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Export Excel
                    </button> --}}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-300">Invoice #</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-300">Tenant</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-300">Due Date</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">Amount</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">Paid</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600 dark:text-gray-300">Balance</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($invoices as $invoice)
                            @php
                                $amountPaid = $invoice->payments->sum('amount_paid') ?? 0;
                                $balance = ($invoice->amount ?? 0) - $amountPaid;
                                $statusColor = match($invoice->status) {
                                    'paid' => 'text-green-600 bg-green-50 dark:bg-green-900/20',
                                    'partial' => 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20',
                                    'unpaid' => 'text-red-600 bg-red-50 dark:bg-red-900/20',
                                    default => 'text-gray-600 bg-gray-50 dark:bg-gray-800',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $invoice->invoice_number }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">{{ optional($invoice->tenant)->tenant_name }}</span>
                                    <br><span class="text-xs text-gray-500">{{ optional($invoice->tenant)->phone_number }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-gray-200">KES {{ number_format($invoice->amount ?? 0) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-green-600 font-medium">KES {{ number_format($amountPaid) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">KES {{ number_format($balance) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                        {{ ucfirst($invoice->status ?? 'n/a') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No invoices found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = {!! json_encode($labels) !!};
        const invoiceData = {!! json_encode($invoiceTotals) !!};
        const paymentData = {!! json_encode($paymentTotals) !!};

        const ctxInv = document.getElementById('chartInvoices');
        if (ctxInv) {
            new Chart(ctxInv.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Invoices', data: invoiceData, backgroundColor: 'rgba(59,130,246,0.8)', borderColor: '#3b82f6', borderWidth: 1 },
                        { label: 'Payments', data: paymentData, backgroundColor: 'rgba(34,197,94,0.8)', borderColor: '#22c55e', borderWidth: 1 },
                    ]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#d1d5db' : '#374151',
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                            }
                        },
                        x: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#d1d5db' : '#374151',
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                            }
                        }
                    }
                }
            });
        }

        const ctxPay = document.getElementById('chartPayments');
        if (ctxPay) {
            new Chart(ctxPay.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Payments', data: paymentData, borderColor: 'rgba(34,197,94,1)', backgroundColor: 'rgba(34,197,94,0.1)', tension: 0.4, fill: true }
                    ]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#d1d5db' : '#374151',
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                            }
                        },
                        x: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#d1d5db' : '#374151',
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                            }
                        }
                    }
                }
            });
        }

        {{-- Export functions disabled for now --}}
        // function exportToPDF() {
        //     const url = new URL(window.location);
        //     url.searchParams.set('export', 'pdf');
        //     window.location.href = url.toString();
        // }
        //
        // function exportToExcel() {
        //     const url = new URL(window.location);
        //     url.searchParams.set('export', 'excel');
        //     window.location.href = url.toString();
        // }
    </script>
</x-filament::page>
</div>
