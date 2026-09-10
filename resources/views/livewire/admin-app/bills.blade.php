<div class="space-y-4">
    @if (session('bill-recorded'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('bill-recorded') }}
        </div>
    @endif

    <div class="flex gap-2">
        @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::CREATE_BILLS))
            <button wire:click="startCreate" class="flex-1 rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
                + Add a bill
            </button>
        @endif
        <button type="button" wire:click="export" class="flex items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 px-4 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
            @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
        </button>
    </div>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit bill' : 'New bill' }}</p>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Tenant</label>
                <select wire:model="tenant_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Select tenant</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->tenant_name }}</option>
                    @endforeach
                </select>
                @error('tenant_id') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Bill month</label>
                <input type="date" wire:model="bill_month" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Water (KES)</label>
                    <input type="number" wire:model="water" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Electricity (KES)</label>
                    <input type="number" wire:model="electricity" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Internet (KES)</label>
                    <input type="number" wire:model="internet" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Trash (KES)</label>
                    <input type="number" wire:model="trash" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Note (optional)</label>
                <textarea wire:model="note" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
            </div>
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="record" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save bill</button>
            </div>
        </div>
    @endif

    <div class="flex gap-2">
        <input type="month" wire:model.live="monthFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
        <select wire:model.live="locationFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <option value="">All properties</option>
            @foreach ($locations as $location)
                <option value="{{ $location->id }}">{{ $location->location_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-3">
        @forelse ($bills as $bill)
            @php $total = $bill->water + $bill->electricity + $bill->internet + $bill->trash; @endphp
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $bill->tenant?->tenant_name ?? 'Unknown' }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($bill->bill_month)->format('F Y') }}</p>
                    </div>
                    <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($total) }}</p>
                </div>
                @if ($bill->note)
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $bill->note }}</p>
                @endif
                @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::EDIT_BILLS) || auth()->user()->hasPermission(\App\Support\StaffPermissions::DELETE_BILLS))
                    <div class="mt-3 flex gap-2">
                        @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::EDIT_BILLS))
                            <button wire:click="startEdit({{ $bill->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                                Edit
                            </button>
                        @endif
                        @if (auth()->user()->hasPermission(\App\Support\StaffPermissions::DELETE_BILLS))
                            <button wire:click="delete({{ $bill->id }})" wire:confirm="Delete this bill record? This can't be undone." class="flex-1 rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                                Delete
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No bills recorded yet.
            </div>
        @endforelse

        <div>{{ $bills->links() }}</div>
    </div>
</div>
