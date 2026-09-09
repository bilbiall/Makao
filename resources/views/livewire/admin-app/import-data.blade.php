@php
    $order = ['locations' => '1', 'houses' => '2', 'tenants' => '3', 'invoices' => '4', 'payments' => '5', 'expenses' => null];
    $active = $importers[$activeKey];
@endphp
<div class="space-y-4">
    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-2 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Bring in your existing data</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Download a template, fill in one row per record, and upload it back. Do them in
            order: Properties &rarr; Units &rarr; Tenants &rarr; Invoices &rarr; Payments -
            each step looks up the ones before it by name/phone number. Expenses is
            independent. Imported records won't trigger welcome SMS or payment
            notifications.
        </p>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-1">
        @foreach ($importers as $key => $info)
            <button
                wire:click="selectImporter('{{ $key }}')"
                class="shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition
                    {{ $activeKey === $key
                        ? 'bg-emerald-600 text-white'
                        : 'bg-white text-slate-600 border border-slate-300 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-700' }}"
            >
                {{ $order[$key] ? $order[$key] . '. ' : '' }}{{ $info['label'] }}
            </button>
        @endforeach
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-4 dark:bg-slate-900 dark:border-slate-800">
        <div class="flex items-center justify-between">
            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $active['label'] }}</p>
            <button wire:click="downloadTemplate('{{ $activeKey }}')" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                Download template
            </button>
        </div>

        <div>
            <input type="file" wire:model="file" accept=".csv,text/csv"
                class="block w-full text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700 dark:file:bg-slate-800 dark:file:text-slate-200">
            @error('file') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            <div wire:loading wire:target="file" class="text-xs text-slate-400 mt-1">Uploading...</div>
        </div>

        <button
            wire:click="import('{{ $activeKey }}')"
            wire:loading.attr="disabled"
            wire:target="import('{{ $activeKey }}')"
            @if (! $file) disabled @endif
            class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition disabled:opacity-50"
        >
            <span wire:loading.remove wire:target="import('{{ $activeKey }}')">Upload &amp; import</span>
            <span wire:loading wire:target="import('{{ $activeKey }}')">Importing...</span>
        </button>
    </div>

    @if ($lastResult)
        <div class="rounded-2xl border p-4 space-y-2
            {{ $lastResult['failuresCount'] === 0
                ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/20'
                : 'bg-amber-50 border-amber-200 dark:bg-amber-500/10 dark:border-amber-500/20' }}">
            <p class="text-sm font-semibold {{ $lastResult['failuresCount'] === 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }}">
                {{ $lastResult['label'] }}: {{ $lastResult['successful'] }} of {{ $lastResult['total'] }} row(s) imported.
            </p>

            @if ($lastResult['failuresCount'] > 0)
                <p class="text-xs text-amber-700 dark:text-amber-400">{{ $lastResult['failuresCount'] }} row(s) failed:</p>
                <ul class="text-xs text-amber-700 dark:text-amber-400 list-disc list-inside space-y-0.5">
                    @foreach ($lastResult['failures'] as $failure)
                        <li>{{ $failure }}</li>
                    @endforeach
                </ul>
                @if ($lastResult['failuresCount'] > count($lastResult['failures']))
                    <p class="text-xs text-amber-600 dark:text-amber-500">...and {{ $lastResult['failuresCount'] - count($lastResult['failures']) }} more.</p>
                @endif
            @endif
        </div>
    @endif
</div>
