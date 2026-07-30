<div class="space-y-4">
    @if (session('bill-recorded'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('bill-recorded') }}
        </div>
    @endif

    <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
        + Add a bill
    </button>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3">
            <div>
                <label class="text-xs font-medium text-slate-600">Tenant</label>
                <select wire:model="tenant_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select tenant</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->tenant_name }}</option>
                    @endforeach
                </select>
                @error('tenant_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Bill month</label>
                <input type="date" wire:model="bill_month" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-slate-600">Water (KES)</label>
                    <input type="number" wire:model="water" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">Electricity (KES)</label>
                    <input type="number" wire:model="electricity" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">Internet (KES)</label>
                    <input type="number" wire:model="internet" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600">Trash (KES)</label>
                    <input type="number" wire:model="trash" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Note (optional)</label>
                <textarea wire:model="note" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            </div>
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-medium text-slate-700">Cancel</button>
                <button wire:click="record" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save bill</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($bills as $bill)
            @php $total = $bill->water + $bill->electricity + $bill->internet + $bill->trash; @endphp
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $bill->tenant?->tenant_name ?? 'Unknown' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ \Carbon\Carbon::parse($bill->bill_month)->format('F Y') }}</p>
                </div>
                <p class="font-semibold text-slate-800">KES {{ number_format($total) }}</p>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
                No bills recorded yet.
            </div>
        @endforelse

        <div>{{ $bills->links() }}</div>
    </div>
</div>
