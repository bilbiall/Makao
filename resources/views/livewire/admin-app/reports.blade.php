<div class="space-y-5">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
        <p class="text-sm font-semibold text-slate-900">Revenue (last 6 months)</p>
        <div class="mt-4 flex items-end gap-2 h-32">
            @foreach ($months as $i => $month)
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full rounded-t bg-emerald-500" style="height: {{ max(4, ($revenues[$i] / $maxRevenue) * 100) }}px"></div>
                    <span class="text-[10px] text-slate-500">{{ $month }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
        <p class="text-sm font-semibold text-slate-900">Invoice status breakdown</p>
        <div class="mt-3 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-lg bg-emerald-50 py-3">
                <p class="text-xl font-bold text-emerald-800">{{ $paidCount }}</p>
                <p class="text-xs text-emerald-700">Paid</p>
            </div>
            <div class="rounded-lg bg-amber-50 py-3">
                <p class="text-xl font-bold text-amber-800">{{ $partialCount }}</p>
                <p class="text-xs text-amber-700">Partial</p>
            </div>
            <div class="rounded-lg bg-rose-50 py-3">
                <p class="text-xl font-bold text-rose-800">{{ $unpaidCount }}</p>
                <p class="text-xs text-rose-700">Unpaid</p>
            </div>
        </div>
    </div>

    <a href="{{ url('/admin/reports') }}" class="block text-center text-sm font-medium text-emerald-700">
        View full report suite &rarr;
    </a>
</div>
