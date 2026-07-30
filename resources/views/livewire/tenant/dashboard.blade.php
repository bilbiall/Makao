<div class="space-y-5">
    <div>
        <p class="text-sm text-slate-500">Welcome back,</p>
        <p class="text-xl font-bold text-slate-900">{{ auth()->user()->name }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
            <p class="text-xs text-slate-500">Your house</p>
            <p class="mt-1 text-base font-semibold text-slate-900">{{ $houseName }}</p>
            <p class="mt-1 text-xs text-slate-500">KES {{ number_format($rentAmount) }}/month</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4 shadow-sm">
            <p class="text-xs text-amber-700">Pending balance</p>
            <p class="mt-1 text-xl font-bold text-amber-800">KES {{ number_format($pendingAmount) }}</p>
            @if ($paymentModeIsAutomatic && $pendingAmount > 0)
                <a href="{{ route('app.tenant.invoices') }}" class="mt-2 inline-block text-xs font-semibold text-amber-800 underline">Pay now &rarr;</a>
            @endif
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-900">Recent invoices</p>
            <a href="{{ route('app.tenant.invoices') }}" class="text-xs font-medium text-emerald-700">See all</a>
        </div>
        @forelse ($recentInvoices as $invoice)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 last:border-0">
                <div>
                    <p class="font-medium text-slate-800">{{ $invoice->invoice_number }}</p>
                    <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-slate-800">KES {{ number_format($invoice->amount) }}</p>
                    <span @class([
                        'inline-block rounded-full px-2 py-0.5 text-[11px] font-medium',
                        'bg-emerald-100 text-emerald-700' => $invoice->status === 'paid',
                        'bg-amber-100 text-amber-700' => $invoice->status === 'partial',
                        'bg-rose-100 text-rose-700' => $invoice->status === 'unpaid',
                    ])>{{ ucfirst($invoice->status) }}</span>
                </div>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 text-center">No invoices yet.</p>
        @endforelse
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-900">Recent payments</p>
            <a href="{{ route('app.tenant.payments') }}" class="text-xs font-medium text-emerald-700">See all</a>
        </div>
        @forelse ($recentPayments as $payment)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 last:border-0">
                <div>
                    <p class="font-medium text-slate-800">{{ $payment->payment_reference ?: 'Payment' }}</p>
                    <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}</p>
                </div>
                <p class="font-semibold text-emerald-700">KES {{ number_format($payment->amount_paid) }}</p>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 text-center">No payments yet.</p>
        @endforelse
    </div>
</div>
