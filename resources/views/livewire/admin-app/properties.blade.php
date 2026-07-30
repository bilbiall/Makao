<div class="space-y-4">
    @forelse ($locations as $location)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100">
                <p class="font-semibold text-slate-900">{{ $location->location_name }}</p>
                <p class="text-xs text-slate-500">{{ $location->houses->count() }} units</p>
            </div>
            <div class="divide-y divide-slate-50">
                @foreach ($location->houses as $house)
                    <div class="flex items-center justify-between px-4 py-2.5 text-sm">
                        <span class="text-slate-700">{{ $house->house_name }}</span>
                        <div class="flex items-center gap-3">
                            <span class="text-slate-500 text-xs">KES {{ number_format($house->rent_amount) }}</span>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[11px] font-medium',
                                'bg-emerald-100 text-emerald-700' => $house->house_status === 'Occupied',
                                'bg-slate-100 text-slate-600' => $house->house_status === 'Vacant',
                            ])>{{ $house->house_status }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
            No properties yet.
        </div>
    @endforelse
</div>
