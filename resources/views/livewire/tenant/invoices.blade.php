<div class="space-y-3" x-data="{ payFor: null }">
    @if (!empty($manualPaymentDetails))
        <div class="rounded-2xl bg-stone-50 border border-slate-200 p-4 dark:bg-slate-800 dark:border-slate-700">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">How to pay</p>
            <div class="mt-2 text-sm text-slate-600 dark:text-slate-400 space-y-1">
                @if (!empty($manualPaymentDetails['bank_name']))
                    <p>Bank: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $manualPaymentDetails['bank_name'] }}</span></p>
                @endif
                @if (!empty($manualPaymentDetails['account_name']))
                    <p>Account name: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $manualPaymentDetails['account_name'] }}</span></p>
                @endif
                @if (!empty($manualPaymentDetails['account_number']))
                    <p>Account number: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $manualPaymentDetails['account_number'] }}</span></p>
                @endif
                @if (!empty($manualPaymentDetails['paybill_number']))
                    <p>Paybill: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $manualPaymentDetails['paybill_number'] }}</span></p>
                @endif
                @if (!empty($manualPaymentDetails['till_number']))
                    <p>Till number: <span class="font-medium text-slate-800 dark:text-slate-200">{{ $manualPaymentDetails['till_number'] }}</span></p>
                @endif
                @if (!empty($manualPaymentDetails['instructions']))
                    <p class="pt-1 text-slate-500 dark:text-slate-400">{{ $manualPaymentDetails['instructions'] }}</p>
                @endif
            </div>
        </div>
    @endif

    @forelse ($invoices as $invoice)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $invoice->invoice_number }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Invoiced {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                        &middot; Due {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                    </p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $invoice->status === 'paid',
                    'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $invoice->status === 'partial',
                    'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' => $invoice->status === 'unpaid',
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

            @if ($paymentModeIsAutomatic && $invoice->balance > 0)
                <button @click="payFor = {{ $invoice->id }}" class="mt-3 w-full rounded-lg bg-emerald-600 text-white text-sm font-semibold py-2.5 hover:bg-emerald-700 transition">
                    Pay Now
                </button>
            @endif
        </div>

        {{-- Pay modal for this invoice --}}
        <div x-show="payFor === {{ $invoice->id }}" x-cloak class="fixed inset-0 z-50 flex items-end lg:items-center justify-center">
            <div class="absolute inset-0 bg-slate-900/40" @click="payFor = null"></div>
            <div class="relative bg-white rounded-t-2xl lg:rounded-2xl w-full lg:max-w-sm p-5 dark:bg-slate-900" x-data="{ method: 'mpesa', amount: {{ $invoice->balance }}, phone: '' }">
                <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">Pay Invoice {{ $invoice->invoice_number }}</p>
                <form :action="method === 'mpesa'
                        ? '{{ route('tenant.mpesa.initiate', ['invoice' => $invoice->id]) }}'
                        : '{{ route('tenant.payments.initiate', ['invoice' => $invoice->id]) }}'"
                      method="GET" class="mt-4 space-y-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Payment method</label>
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <button type="button" @click="method = 'mpesa'" :class="method === 'mpesa' ? 'border-emerald-600 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'" class="rounded-lg border px-3 py-2 text-sm font-medium">M-Pesa</button>
                            <button type="button" @click="method = 'pesapal'" :class="method === 'pesapal' ? 'border-emerald-600 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400'" class="rounded-lg border px-3 py-2 text-sm font-medium">Pesapal</button>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Amount (KES)</label>
                        <input type="number" name="amount" x-model="amount" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div x-show="method === 'mpesa'">
                        <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Phone number</label>
                        <input type="text" name="phone_number" x-model="phone" placeholder="0712345678" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="payFor = null" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Continue</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No invoices yet.
        </div>
    @endforelse

    <div>
        {{ $invoices->links() }}
    </div>
</div>
