<div class="space-y-3">
    @forelse ($packages as $package)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $package->name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">KES {{ number_format($package->price) }} / {{ $package->billing_interval }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-emerald-100 text-emerald-700' => $package->is_active,
                    'bg-slate-100 text-slate-600' => !$package->is_active,
                ])>{{ $package->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-lg bg-slate-50 py-2">
                    <p class="font-semibold text-slate-800">{{ $package->max_locations ?? 'Unlimited' }}</p>
                    <p class="text-slate-500">Properties</p>
                </div>
                <div class="rounded-lg bg-slate-50 py-2">
                    <p class="font-semibold text-slate-800">{{ $package->max_houses ?? 'Unlimited' }}</p>
                    <p class="text-slate-500">Units</p>
                </div>
                <div class="rounded-lg bg-slate-50 py-2">
                    <p class="font-semibold text-slate-800">{{ $package->max_tenants ?? 'Unlimited' }}</p>
                    <p class="text-slate-500">Tenants</p>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
            No packages configured yet.
        </div>
    @endforelse
</div>
