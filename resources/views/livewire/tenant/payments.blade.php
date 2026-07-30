<div class="space-y-3">
    @forelse ($payments as $payment)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between">
            <div>
                <p class="font-semibold text-slate-900">{{ $payment->payment_reference ?: 'Payment #' . $payment->id }}</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    {{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}
                    &middot; {{ strtoupper($payment->payment_method ?? 'manual') }}
                </p>
                @if ($payment->invoice)
                    <p class="text-xs text-slate-400 mt-0.5">For {{ $payment->invoice->invoice_number }}</p>
                @endif
            </div>
            <p class="font-semibold text-emerald-700">KES {{ number_format($payment->amount_paid) }}</p>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
            No payments recorded yet.
        </div>
    @endforelse

    <div>{{ $payments->links() }}</div>
</div>
