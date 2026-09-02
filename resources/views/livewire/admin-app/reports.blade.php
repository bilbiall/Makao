<div class="space-y-5">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">From</label>
                <input type="date" wire:model.live="from" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">To</label>
                <input type="date" wire:model.live="to" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>
        </div>
        <div>
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Search tenant (name or phone)</label>
            <input type="text" wire:model.live.debounce.400ms="tenant_search" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
        </div>
        <div>
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Invoice status</label>
            <select wire:model.live="invoice_status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <option value="">All</option>
                <option value="overdue">Overdue</option>
                <option value="due">Due Today</option>
                <option value="upcoming">Upcoming</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="unpaid">Unpaid</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
            <p class="text-[11px] text-emerald-700">Invoiced</p>
            <p class="mt-1 text-sm font-bold text-emerald-800">KES {{ number_format($summary['total_invoiced'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-3">
            <p class="text-[11px] text-amber-700">Paid</p>
            <p class="mt-1 text-sm font-bold text-amber-800">KES {{ number_format($summary['total_paid'] ?? 0) }}</p>
        </div>
        <div class="rounded-2xl bg-rose-50 border border-rose-100 p-3">
            <p class="text-[11px] text-rose-700">Outstanding</p>
            <p class="mt-1 text-sm font-bold text-rose-800">KES {{ number_format($summary['outstanding'] ?? 0) }}</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                Invoices{{ $invoice_status_label ? " - {$invoice_status_label}" : '' }}
            </p>
            <div class="flex gap-3 text-xs font-medium">
                <button wire:click="exportPdf" class="text-emerald-700">PDF</button>
                <button wire:click="exportExcel" class="text-emerald-700">Export</button>
            </div>
        </div>
        @forelse ($invoices as $invoice)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 dark:border-slate-800/60 last:border-0">
                <div>
                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ $invoice->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $invoice->invoice_number }} &middot; {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($invoice->amount) }}</p>
                    <span @class([
                        'text-[11px] font-medium',
                        'text-emerald-600' => $invoice->status === 'paid',
                        'text-amber-600' => $invoice->status === 'partial',
                        'text-rose-600' => $invoice->status === 'unpaid',
                    ])>{{ ucfirst($invoice->status) }}</span>
                </div>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400 text-center">No invoices in this period.</p>
        @endforelse
    </div>
</div>
