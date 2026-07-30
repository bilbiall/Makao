<div class="space-y-3">
    @forelse ($landlords as $landlord)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $landlord->name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $landlord->contact_email }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-emerald-100 text-emerald-700' => $landlord->status === 'active',
                    'bg-rose-100 text-rose-700' => $landlord->status === 'suspended',
                ])>{{ ucfirst($landlord->status) }}</span>
            </div>
            @if ($landlord->currentSubscription)
                <p class="mt-2 text-xs text-slate-500">
                    {{ $landlord->currentSubscription->package?->name }} &middot;
                    <span class="capitalize">{{ $landlord->currentSubscription->status }}</span>
                </p>
            @endif
            <a href="{{ url('/superadmin/landlords/' . $landlord->id . '/settings') }}" class="mt-3 block text-center rounded-lg border border-slate-300 py-2 text-xs font-medium text-slate-700">
                Manage settings
            </a>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
            No landlords yet.
        </div>
    @endforelse

    <div>{{ $landlords->links() }}</div>
</div>
