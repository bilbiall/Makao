@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-4">
    @if (session('package-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('package-saved') }}
        </div>
    @endif
    @if (session('package-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('package-error') }}
        </div>
    @endif

    @if (!$showForm)
        <button wire:click="startCreate" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
            + Add package
        </button>
    @endif

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit package' : 'New package' }}</p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $labelClass }}">Name</label>
                    <input type="text" wire:model.live="name" class="{{ $inputClass }}">
                    @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Slug</label>
                    <input type="text" wire:model="slug" class="{{ $inputClass }}">
                    @error('slug') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Price (KES)</label>
                    <input type="number" step="0.01" wire:model="price" class="{{ $inputClass }}">
                    @error('price') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Billing interval</label>
                    <select wire:model="billing_interval" class="{{ $inputClass }}">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Trial days</label>
                    <input type="number" wire:model="trial_days" class="{{ $inputClass }}">
                    @error('trial_days') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Sort order</label>
                    <input type="number" wire:model="sort_order" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Limits</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Leave blank for unlimited.</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Max properties</label>
                        <input type="number" wire:model="max_locations" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Max units</label>
                        <input type="number" wire:model="max_houses" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Max tenants</label>
                        <input type="number" wire:model="max_tenants" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Max staff seats</label>
                        <input type="number" wire:model="max_users" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Features</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">What this plan unlocks for a landlord.</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" wire:model="feature_sms_notifications" class="rounded border-slate-300 dark:border-slate-600">
                        SMS notifications
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" wire:model="feature_mpesa_payments" class="rounded border-slate-300 dark:border-slate-600">
                        M-Pesa payments
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" wire:model="feature_reports" class="rounded border-slate-300 dark:border-slate-600">
                        Reports &amp; analytics
                    </label>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 pt-2 border-t border-slate-100 dark:border-slate-800">
                <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 dark:border-slate-600">
                Active (visible on the pricing page)
            </label>

            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($packages as $package)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $package->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">KES {{ number_format($package->price) }} / {{ $package->billing_interval }}</p>
                    </div>
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-medium',
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $package->is_active,
                        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => !$package->is_active,
                    ])>{{ $package->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $package->max_locations ?? '&infin;' }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Properties</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $package->max_houses ?? '&infin;' }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Units</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $package->max_tenants ?? '&infin;' }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Tenants</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800 py-2">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $package->max_users ?? '&infin;' }}</p>
                        <p class="text-slate-500 dark:text-slate-400">Staff</p>
                    </div>
                </div>
                @if (array_filter($package->features ?? []))
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach (['sms_notifications' => 'SMS', 'mpesa_payments' => 'M-Pesa', 'reports' => 'Reports'] as $key => $label)
                            @if ($package->features[$key] ?? false)
                                <span class="rounded-full bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:text-indigo-400">{{ $label }}</span>
                            @endif
                        @endforeach
                    </div>
                @endif
                <div class="mt-3 flex gap-2">
                    <button wire:click="startEdit({{ $package->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                        Edit
                    </button>
                    <button wire:click="delete({{ $package->id }})" wire:confirm="Delete this package? This can't be undone." class="flex-1 rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                        Delete
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No packages configured yet.
            </div>
        @endforelse
    </div>
</div>
