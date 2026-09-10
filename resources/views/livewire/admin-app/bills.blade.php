@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
@endphp
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
        @if ($canManageBillTypes)
            <a href="{{ route('app.admin.bill-types') }}" class="flex items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 px-4 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Manage bill types">
                @svg('heroicon-o-adjustments-horizontal', 'w-5 h-5')
            </a>
        @endif
    </div>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit bill' : 'New bill' }}</p>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Tenant</label>
                <select wire:model="tenant_id" class="{{ $inputClass }}">
                    <option value="">Select tenant</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->tenant_name }}</option>
                    @endforeach
                </select>
                @error('tenant_id') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Bill month</label>
                <input type="date" wire:model="bill_month" class="{{ $inputClass }}">
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Charges</label>

                @if ($billTypes->isEmpty())
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">No bill types set up yet - add one below to get started.</p>
                @endif

                <div class="mt-1 space-y-2">
                    @foreach ($bill_lines as $index => $line)
                        <div class="flex gap-2">
                            <select wire:model.live="bill_lines.{{ $index }}.bill_type_id" class="{{ $inputClass }} mt-0 flex-1">
                                <option value="">Select type</option>
                                @foreach ($billTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" wire:model="bill_lines.{{ $index }}.amount" placeholder="Amount" class="{{ $inputClass }} mt-0 w-28">
                            <button type="button" wire:click="removeBillLine({{ $index }})" class="flex-shrink-0 rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-500 dark:text-slate-400" title="Remove">
                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                            </button>
                        </div>
                        @error("bill_lines.{$index}.bill_type_id") <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        @error("bill_lines.{$index}.amount") <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    @endforeach
                </div>

                <button type="button" wire:click="addBillLine" class="mt-2 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                    + Add another charge
                </button>

                @if ($canManageBillTypes)
                    <div class="mt-3 rounded-lg border border-dashed border-slate-300 dark:border-slate-700 p-3">
                        @if (!$showQuickAddType)
                            <button type="button" wire:click="$set('showQuickAddType', true)" class="text-xs font-medium text-slate-600 dark:text-slate-400">
                                + New bill type not listed above
                            </button>
                        @else
                            <div class="space-y-2">
                                <input type="text" wire:model="new_type_name" placeholder="Type name, e.g. Security" class="{{ $inputClass }} mt-0">
                                @error('new_type_name') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                <div class="flex gap-2">
                                    <input type="number" wire:model="new_type_default_amount" placeholder="Default amount (optional)" class="{{ $inputClass }} mt-0 flex-1">
                                    <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 flex-shrink-0">
                                        <input type="checkbox" wire:model="new_type_recurring" class="rounded border-slate-300 dark:border-slate-700">
                                        Recurring
                                    </label>
                                </div>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Applies to all your properties by default - manage per-property scoping under Bill Types.</p>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="$set('showQuickAddType', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                                    <button type="button" wire:click="quickAddBillType" class="flex-1 rounded-lg bg-slate-800 dark:bg-slate-700 py-1.5 text-xs font-semibold text-white">Add type</button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Note (optional)</label>
                <textarea wire:model="note" rows="2" class="{{ $inputClass }}"></textarea>
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
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $bill->tenant?->tenant_name ?? 'Unknown' }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($bill->bill_month)->format('F Y') }}</p>
                    </div>
                    <p class="font-semibold text-slate-800 dark:text-slate-200">KES {{ number_format($bill->total) }}</p>
                </div>
                @if ($bill->items->isNotEmpty())
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ $bill->items->map(fn ($item) => ($item->billType?->name ?? 'Charge') . ': KES ' . number_format($item->amount))->implode(' · ') }}
                    </p>
                @endif
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
