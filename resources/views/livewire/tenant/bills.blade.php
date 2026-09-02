<div class="space-y-3">
    @forelse ($bills as $bill)
        @php $total = $bill->water + $bill->electricity + $bill->internet + $bill->trash; @endphp
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-center justify-between">
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ \Carbon\Carbon::parse($bill->bill_month)->format('F Y') }}</p>
                <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($total) }}</p>
            </div>
            <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
                <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                    <p class="text-slate-500 dark:text-slate-400">Water</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($bill->water) }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                    <p class="text-slate-500 dark:text-slate-400">Electricity</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($bill->electricity) }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                    <p class="text-slate-500 dark:text-slate-400">Internet</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($bill->internet) }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                    <p class="text-slate-500 dark:text-slate-400">Trash</p>
                    <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($bill->trash) }}</p>
                </div>
            </div>
            @if ($bill->note)
                <p class="mt-3 text-xs text-slate-500">{{ $bill->note }}</p>
            @endif
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No bills recorded yet.
        </div>
    @endforelse

    <div>{{ $bills->links() }}</div>
</div>
