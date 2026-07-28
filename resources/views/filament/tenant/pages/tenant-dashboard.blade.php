<x-filament::page>
    <div class="space-y-8">

        {{-- House Information --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Tenant's House --}}
            <div class="dark:bg-gray-900 bg-white p-4 shadow rounded">
                <h2 class="text-lg font-semibold dark:text-gray-100">House</h2>
                <p class="text-xl dark:text-gray-300">{{ $houseName }}</p>
            </div>

            {{-- Pending Rent --}}
            <div class="dark:bg-gray-900 bg-white p-4 shadow rounded">
                <h2 class="text-lg font-semibold dark:text-gray-100">Pending Rent</h2>
                <p class="text-xl text-red-600">KES {{ number_format($pendingAmount) }}</p>
            </div>

        </div>

        {{-- Recent Invoices --}}
        <div>
            <h3 class="text-lg font-semibold dark:text-gray-100">Recent Invoices</h3>
            <table class="w-full border dark:border-gray-700">
                <thead class="dark:bg-gray-800 bg-gray-100">
                    <tr>
                        <th class="p-2 border dark:border-gray-700 dark:text-gray-300">#</th>
                        <th class="p-2 border dark:border-gray-700 dark:text-gray-300">Total</th>
                        <th class="p-2 border dark:border-gray-700 dark:text-gray-300">Balance</th>
                        <th class="p-2 border dark:border-gray-700 dark:text-gray-300">Date</th>
                    </tr>
                </thead>
                <tbody class="dark:bg-gray-900 bg-white">
                @forelse ($recentInvoices as $invoice)
                    <tr class="dark:border-gray-700">
                        <td class="p-2 border dark:border-gray-700 dark:text-gray-300">{{ $invoice->invoice_number }}</td>
                        <td class="p-2 border dark:border-gray-700 dark:text-gray-300">{{ number_format($invoice->amount) }}</td>
                        <td class="p-2 border dark:border-gray-700 font-semibold {{ $invoice->payment_balance == 0 ? 'text-yellow-500' : ($invoice->payment_balance < 0 ? 'text-green-600' : 'text-red-600') }}">{{ number_format($invoice->payment_balance) }}</td>
                        <td class="p-2 border dark:border-gray-700 dark:text-gray-300">{{ $invoice->invoice_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center p-2 border dark:border-gray-700 dark:text-gray-400">No invoices yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Recent Payments --}}
        <div>
            <h3 class="text-lg font-semibold dark:text-gray-100">Recent Payments</h3>
            <table class="w-full border dark:border-gray-700">
                <thead class="dark:bg-gray-800 bg-gray-100">
                    <tr>
                        <th class="p-2 border dark:border-gray-700 dark:text-gray-300">#</th>
                        <th class="p-2 border dark:border-gray-700 dark:text-gray-300">Amount Paid</th>
                        <th class="p-2 border dark:border-gray-700 dark:text-gray-300">Date</th>
                    </tr>
                </thead>
                <tbody class="dark:bg-gray-900 bg-white">
                @forelse ($recentPayments as $payment)
                    <tr class="dark:border-gray-700">
                        <td class="p-2 border dark:border-gray-700 dark:text-gray-300">{{ $payment->id }}</td>
                        <td class="p-2 border dark:border-gray-700 dark:text-gray-300">{{ number_format($payment->amount_paid) }}</td>
                        <td class="p-2 border dark:border-gray-700 dark:text-gray-300">{{ $payment->payment_date ?? $payment->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center p-2 border dark:border-gray-700 dark:text-gray-400">No payments yet</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-filament::page>
