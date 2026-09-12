<div class="space-y-4">
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-2xl bg-white border border-slate-200 p-4 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-xs text-slate-500 dark:text-slate-400">Views</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($totals['views']) }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-xs text-slate-500 dark:text-slate-400">Inquiries</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($totals['inquiries']) }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-xs text-slate-500 dark:text-slate-400">Bookings</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($totals['bookings']) }}</p>
        </div>
    </div>

    <div class="space-y-2">
        @forelse ($rows as $row)
            @php
                $house = $row['house'];
                $inquiryRate = $row['views'] > 0 ? round($row['inquiries'] / $row['views'] * 100, 1) : null;
                $bookingRate = $row['views'] > 0 ? round($row['bookings'] / $row['views'] * 100, 1) : null;
            @endphp
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $house->publicName() }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $house->location?->location_name }}</p>
                    </div>
                    <a href="{{ route('stays.show', $house) }}" target="_blank" class="text-xs font-medium text-emerald-700 dark:text-emerald-400 flex-shrink-0">View listing</a>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ number_format($row['views']) }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Views</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ number_format($row['inquiries']) }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Inquiries{{ $inquiryRate !== null ? " ({$inquiryRate}%)" : '' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ number_format($row['bookings']) }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Bookings{{ $bookingRate !== null ? " ({$bookingRate}%)" : '' }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No short-stay properties to report on yet.
            </div>
        @endforelse
    </div>
</div>
