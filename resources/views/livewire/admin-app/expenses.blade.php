@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-4">
    @if (session('expense-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('expense-saved') }}
        </div>
    @endif

    @if ($this->canManageExpenses())
        @if (!$showForm)
            <div class="flex gap-2">
                <button wire:click="startCreate" class="flex-1 rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
                    + Record expense
                </button>
                <button type="button" wire:click="export" class="flex items-center justify-center rounded-xl border border-slate-300 dark:border-slate-700 px-4 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
                    @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
                </button>
            </div>
        @else
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit expense' : 'New expense' }}</p>

                <div>
                    <label class="{{ $labelClass }}">Month</label>
                    <input type="date" wire:model="expense_month" class="{{ $inputClass }}">
                    @error('expense_month') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Electricity (KES)</label>
                        <input type="number" step="0.01" wire:model="electricity" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Water (KES)</label>
                        <input type="number" step="0.01" wire:model="water" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Internet (KES)</label>
                        <input type="number" step="0.01" wire:model="internet" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Maintenance (KES)</label>
                        <input type="number" step="0.01" wire:model="maintenance" class="{{ $inputClass }}">
                    </div>
                    <div class="col-span-2">
                        <label class="{{ $labelClass }}">Other expenses (KES)</label>
                        <input type="number" step="0.01" wire:model="other" class="{{ $inputClass }}">
                    </div>
                </div>

                <div>
                    <label class="{{ $labelClass }}">Notes</label>
                    <input type="text" wire:model="notes" placeholder="e.g. fuel, generator repair" class="{{ $inputClass }}">
                </div>

                <div class="flex gap-3">
                    <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                    <button wire:click="save" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
                </div>
            </div>
        @endif
    @endif

    <div class="space-y-3">
        @forelse ($expenses as $expense)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $expense->expense_month->format('F Y') }}</p>
                        @if ($expense->notes)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $expense->notes }}</p>
                        @endif
                    </div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">KES {{ number_format($expense->total(), 2) }}</p>
                </div>
                <div class="mt-3 grid grid-cols-5 gap-2 text-center text-xs">
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($expense->electricity) }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Electricity</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($expense->water) }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Water</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($expense->internet) }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Internet</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($expense->maintenance) }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Maintenance</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ number_format($expense->other) }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Other</p>
                    </div>
                </div>
                @if ($this->canManageExpenses())
                    <div class="mt-3 flex gap-2">
                        <button wire:click="startEdit({{ $expense->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                            Edit
                        </button>
                        <button wire:click="delete({{ $expense->id }})" wire:confirm="Delete this expense record? This can't be undone." class="flex-1 rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                            Delete
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No expenses recorded yet.
            </div>
        @endforelse
    </div>

    <div>{{ $expenses->links() }}</div>
</div>
