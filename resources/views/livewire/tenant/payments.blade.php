<div class="space-y-3">
    @forelse ($payments as $payment)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between dark:bg-slate-900 dark:border-slate-800">
            <div>
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $payment->payment_reference ?: 'Payment #' . $payment->id }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}
                    &middot; {{ strtoupper($payment->payment_method ?? 'manual') }}
                </p>
                @if ($payment->invoice)
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">For {{ $payment->invoice->invoice_number }}</p>
                @endif
            </div>
            <p class="font-semibold text-emerald-700 dark:text-emerald-400">KES {{ number_format($payment->amount_paid) }}</p>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No payments recorded yet.
        </div>
    @endforelse

    <div>{{ $payments->links() }}</div>
</div>
