<div class="space-y-4">
    @if (session('payment-recorded'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('payment-recorded') }}
        </div>
    @endif

    <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
        + Record a payment
    </button>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3">
            <div>
                <label class="text-xs font-medium text-slate-600">Tenant</label>
                <select wire:model.live="tenant_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select tenant</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->tenant_name }}</option>
                    @endforeach
                </select>
                @error('tenant_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Invoice</label>
                <select wire:model="invoice_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select invoice</option>
                    @foreach ($invoiceOptions as $invoice)
                        <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - Balance KES {{ number_format($invoice->balance) }}</option>
                    @endforeach
                </select>
                @error('invoice_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Amount paid (KES)</label>
                <input type="number" wire:model="amount_paid" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('amount_paid') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Payment method</label>
                <select wire:model="payment_method" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank transfer</option>
                    <option value="mpesa">M-Pesa (manual entry)</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Reference</label>
                <input type="text" wire:model="payment_reference" placeholder="e.g. till slip number, M-Pesa code" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('payment_reference') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Payment date</label>
                <input type="date" wire:model="payment_date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-medium text-slate-700">Cancel</button>
                <button wire:click="record" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save payment</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($payments as $payment)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $payment->tenant?->tenant_name ?? 'Unknown' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $payment->invoice?->invoice_number }} &middot;
                        {{ \Carbon\Carbon::parse($payment->payment_date ?? $payment->created_at)->format('d M Y') }}
                    </p>
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
</div>
