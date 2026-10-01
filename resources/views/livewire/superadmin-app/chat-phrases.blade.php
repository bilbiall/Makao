@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-4">
    @if (session('chat-phrase-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('chat-phrase-saved') }}
        </div>
    @endif

    <p class="text-xs text-slate-500 dark:text-slate-400">Words the chat assistant has been taught, on top of its built-in Swahili basics. Add area nicknames ("Kasa" → Kasarani), ways of saying yes/no, unit-type phrases, or ways of asking for a person.</p>

    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search phrases..." class="{{ $inputClass }}">

    @if (! $showForm)
        <button wire:click="create" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">+ Teach a phrase</button>
    @else
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit phrase' : 'New phrase' }}</p>

            <div>
                <label class="{{ $labelClass }}">Type</label>
                <select wire:model.live="type" class="{{ $inputClass }}">
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $labelClass }}">What a visitor types</label>
                <input type="text" wire:model="phrase" placeholder="e.g. kasa" class="{{ $inputClass }}">
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Not case sensitive.</p>
                @error('phrase') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            @if (in_array($type, ['area', 'house_type'], true))
                <div>
                    <label class="{{ $labelClass }}">Means</label>
                    <select wire:model="canonical" class="{{ $inputClass }}">
                        <option value="">Choose…</option>
                        @foreach ($canonicalOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('canonical') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="{{ $labelClass }}">Language</label>
                <select wire:model="language" class="{{ $inputClass }}">
                    <option value="sw">Swahili / Sheng</option>
                    <option value="en">English</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button wire:click="cancel" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="save" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
            </div>
        </div>
    @endif

    <div class="space-y-2">
        @forelse ($aliases as $alias)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800" wire:key="alias-{{ $alias->id }}">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900 dark:text-slate-100 truncate">“{{ $alias->phrase }}”@if ($alias->canonical) <span class="text-slate-400">→</span> {{ $alias->canonical }}@endif</p>
                        <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">{{ $types[$alias->type] ?? $alias->type }} · {{ $alias->language === 'sw' ? 'Swahili' : ($alias->language === 'en' ? 'English' : 'any language') }} · used {{ $alias->uses_count }}×</p>
                    </div>

                    <button type="button" wire:click="toggleActive({{ $alias->id }})" role="switch" aria-checked="{{ $alias->is_active ? 'true' : 'false' }}" aria-label="{{ $alias->phrase }} is {{ $alias->is_active ? 'active' : 'off' }}"
                        @class([
                            'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors',
                            'bg-emerald-600' => $alias->is_active,
                            'bg-slate-300 dark:bg-slate-700' => ! $alias->is_active,
                        ])>
                        <span @class([
                            'inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform',
                            'translate-x-6' => $alias->is_active,
                            'translate-x-1' => ! $alias->is_active,
                        ])></span>
                    </button>
                </div>
                <div class="mt-2 flex gap-4 text-xs font-medium">
                    <button wire:click="edit({{ $alias->id }})" class="text-emerald-700 dark:text-emerald-400">Edit</button>
                    <button wire:click="delete({{ $alias->id }})" wire:confirm="Delete this phrase?" class="text-rose-600 dark:text-rose-400">Delete</button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                Nothing taught yet. Add one above, or use <strong>Teach</strong> on a message in the failure inbox.
            </div>
        @endforelse
    </div>

    <div>{{ $aliases->links() }}</div>
</div>
