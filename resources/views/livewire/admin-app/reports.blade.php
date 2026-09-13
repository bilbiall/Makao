<div class="space-y-4">
    {{-- Tab switcher --}}
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
        @foreach (\App\Livewire\AdminApp\Reports::TABS as $key => $label)
            @continue(! in_array($key, $allowedTabs))
            <button
                wire:click="selectTab('{{ $key }}')"
                @class([
                    'flex-shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold whitespace-nowrap transition',
                    'bg-emerald-600 text-white' => $tab === $key,
                    'bg-white text-slate-600 border border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-slate-800' => $tab !== $key,
                ])
            >{{ $label }}</button>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
        @if (in_array($tab, ['overview', 'bnb', 'income_statement']))
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">From</label>
                    <input type="date" wire:model.live="from" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">To</label>
                    <input type="date" wire:model.live="to" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
            </div>
        @endif

        @if (in_array($tab, ['overview', 'rent_roll', 'arrears', 'bnb']) && count($locations) > 0)
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Property</label>
                <select wire:model.live="location_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">All properties</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($tab === 'overview')
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Search tenant (name or phone)</label>
                <input type="text" wire:model.live.debounce.400ms="tenant_search" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Invoice status</label>
                <select wire:model.live="invoice_status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">All</option>
                    <option value="overdue">Overdue</option>
                    <option value="due">Due Today</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </div>
        @endif
    </div>

    {{-- Export bar --}}
    <div class="flex items-center justify-end gap-4 text-xs font-semibold">
        <button wire:click="exportPdf" class="flex items-center gap-1 text-emerald-700 dark:text-emerald-400">
            @svg('heroicon-o-printer', 'w-4 h-4') PDF
        </button>
        <button wire:click="exportCsv" class="flex items-center gap-1 text-emerald-700 dark:text-emerald-400">
            @svg('heroicon-o-arrow-down-tray', 'w-4 h-4') CSV
        </button>
    </div>

    @if ($tab === 'overview')
        @include('livewire.admin-app.reports.overview')
    @elseif ($tab === 'rent_roll')
        @include('livewire.admin-app.reports.rent-roll')
    @elseif ($tab === 'arrears')
        @include('livewire.admin-app.reports.arrears')
    @elseif ($tab === 'bnb')
        @include('livewire.admin-app.reports.bnb')
    @elseif ($tab === 'income_statement')
        @include('livewire.admin-app.reports.income-statement')
    @endif
</div>
