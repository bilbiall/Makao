<div class="space-y-4">
    <div class="grid grid-cols-2 gap-2">
        @foreach ($data['buckets'] as $bucket)
            <x-admin.stat-tile
                :label="$bucket['label']"
                value="KES {{ number_format($bucket['total']) }}"
                :color="match($bucket['key']) { 'current' => 'amber', 'bucket_31_60' => 'amber', default => 'rose' }"
            />
        @endforeach
    </div>

    <div class="rounded-2xl bg-rose-50 border border-rose-100 p-4 flex items-center justify-between dark:bg-rose-500/10 dark:border-rose-500/20">
        <div>
            <p class="text-xs text-rose-700 dark:text-rose-400">Total overdue</p>
            <p class="mt-1 text-xl font-bold text-rose-800 dark:text-rose-300">KES {{ number_format($data['grand_total']) }}</p>
        </div>
        <p class="text-xs text-rose-700 dark:text-rose-400 text-right">{{ $data['invoice_count'] }} invoice(s)<br>{{ $data['tenant_count'] }} tenant(s)</p>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        @forelse ($data['rows'] as $row)
            <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 dark:border-slate-800/60 last:border-0">
                <div>
                    <p class="font-medium text-slate-800 dark:text-slate-200">{{ $row['tenant_name'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $row['location_name'] }} &middot; {{ $row['unit'] }} &middot; {{ $row['invoice_number'] }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-rose-700 dark:text-rose-400">KES {{ number_format($row['balance']) }}</p>
                    <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $row['days_overdue'] }}d overdue &middot; {{ $row['bucket_label'] }}</p>
                </div>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400 text-center">No overdue invoices. Everyone is current.</p>
        @endforelse
    </div>
</div>
