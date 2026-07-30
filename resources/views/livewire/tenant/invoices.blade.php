<div class="space-y-3" x-data="{ payFor: null }">
    @forelse ($invoices as $invoice)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Invoiced {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                        &middot; Due {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                    </p>
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
                    <p class="text-slate-500 text-xs">Amount</p>
                    <p class="font-semibold text-slate-800">KES {{ number_format($invoice->amount) }}</p>
                </div>
                <div>
                    <p class="text-slate-500 text-xs">Balance</p>
                    <p class="font-semibold text-slate-800">KES {{ number_format($invoice->balance) }}</p>
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
            <div class="relative bg-white rounded-t-2xl lg:rounded-2xl w-full lg:max-w-sm p-5" x-data="{ method: 'mpesa', amount: {{ $invoice->balance }}, phone: '' }">
                <p class="text-lg font-semibold text-slate-900">Pay Invoice {{ $invoice->invoice_number }}</p>
                <form :action="method === 'mpesa'
                        ? '{{ route('tenant.mpesa.initiate', ['invoice' => $invoice->id]) }}'
                        : '{{ route('tenant.payments.initiate', ['invoice' => $invoice->id]) }}'"
                      method="GET" class="mt-4 space-y-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600">Payment method</label>
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <button type="button" @click="method = 'mpesa'" :class="method === 'mpesa' ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600'" class="rounded-lg border px-3 py-2 text-sm font-medium">M-Pesa</button>
                            <button type="button" @click="method = 'pesapal'" :class="method === 'pesapal' ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-600'" class="rounded-lg border px-3 py-2 text-sm font-medium">Pesapal</button>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600">Amount (KES)</label>
                        <input type="number" name="amount" x-model="amount" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div x-show="method === 'mpesa'">
                        <label class="text-xs font-medium text-slate-600">Phone number</label>
                        <input type="text" name="phone_number" x-model="phone" placeholder="0712345678" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="payFor = null" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-medium text-slate-700">Cancel</button>
                        <button type="submit" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Continue</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
            No invoices yet.
        </div>
    @endforelse

    <div>
        {{ $invoices->links() }}
    </div>
</div>
