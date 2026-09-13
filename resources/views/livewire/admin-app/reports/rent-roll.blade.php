<div class="space-y-4">
    <div class="grid grid-cols-3 gap-2">
        <x-admin.stat-tile label="Tenants" value="{{ $data['totals']['tenant_count'] }}" color="slate" />
        <x-admin.stat-tile label="Total rent" value="KES {{ number_format($data['totals']['total_rent']) }}" color="emerald" />
        <x-admin.stat-tile label="Balances owed" value="KES {{ number_format($data['totals']['total_balance']) }}" color="rose" />
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
        @forelse ($data['properties'] as $property)
            <div class="border-b border-slate-100 last:border-0 dark:border-slate-800">
                <div class="flex items-center justify-between px-4 py-2 bg-slate-50 dark:bg-slate-800/60">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $property['location_name'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $property['tenants']->count() }} tenant(s)</p>
                </div>
                @foreach ($property['tenants'] as $tenant)
                    <div class="flex items-center justify-between px-4 py-3 text-sm border-b border-slate-50 dark:border-slate-800/60 last:border-0">
                        <div>
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ $tenant['tenant_name'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $tenant['unit'] }} &middot; {{ $tenant['phone_number'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($tenant['rent_amount']) }}</p>
                            @if ($tenant['balance'] > 0)
                                <p class="text-[11px] font-medium text-rose-600">Owes KES {{ number_format($tenant['balance']) }}</p>
                            @else
                                <p class="text-[11px] font-medium text-emerald-600">Settled</p>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="flex items-center justify-between px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400">
                    <span>Subtotal</span>
                    <span>KES {{ number_format($property['subtotal_rent']) }} &middot; Balance KES {{ number_format($property['subtotal_balance']) }}</span>
                </div>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-slate-500 dark:text-slate-400 text-center">No admitted tenants yet.</p>
        @endforelse
    </div>
</div>
