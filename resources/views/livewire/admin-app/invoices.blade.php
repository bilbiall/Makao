<div class="space-y-3">
    <select wire:model.live="statusFilter" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">All statuses</option>
        <option value="paid">Paid</option>
        <option value="partial">Partial</option>
        <option value="unpaid">Unpaid</option>
    </select>

    @forelse ($invoices as $invoice)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $invoice->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
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
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
            No invoices found.
        </div>
    @endforelse

    <div>{{ $invoices->links() }}</div>
</div>
