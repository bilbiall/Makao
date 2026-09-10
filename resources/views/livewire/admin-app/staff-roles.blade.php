@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-4">
    @if (session('staff-role-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('staff-role-saved') }}
        </div>
    @endif
    @if (session('staff-role-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('staff-role-error') }}
        </div>
    @endif

    @if (!$showForm)
        <button type="button" wire:click="startCreate" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
            + New staff role
        </button>
    @else
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-4 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit staff role' : 'New staff role' }}</p>

            <div>
                <label class="{{ $labelClass }}">Role name</label>
                <input type="text" wire:model="name" placeholder="e.g. Front Desk" class="{{ $inputClass }}">
                @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Scope</label>
                <div class="mt-1 space-y-2">
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 dark:border-slate-700 p-3 text-sm text-slate-700 dark:text-slate-300">
                        <input type="radio" wire:model="scope_type" value="location" class="mt-0.5">
                        <span>
                            <span class="font-medium block">Property-based</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Like Manager/Caretaker - sees everything in their assigned properties.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 dark:border-slate-700 p-3 text-sm text-slate-700 dark:text-slate-300">
                        <input type="radio" wire:model="scope_type" value="house" class="mt-0.5">
                        <span>
                            <span class="font-medium block">Unit-based</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Like Agent - sees only their directly assigned units.</span>
                        </span>
                    </label>
                </div>
                @error('scope_type') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="{{ $labelClass }}">Permissions</p>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 mb-2">Leave everything unchecked for a view-only role.</p>
                <div class="space-y-3">
                    @foreach ($catalog as $group => $options)
                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 p-3">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">{{ $group }}</p>
                            <div class="space-y-1.5">
                                @foreach ($options as $slug => $label)
                                    <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                        <input type="checkbox" wire:model="permissions" value="{{ $slug }}" class="rounded border-slate-300 dark:border-slate-700">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button type="button" wire:click="save" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($roles as $role)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $role->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $role->scope_type === 'house' ? 'Unit-based' : 'Property-based' }}
                            &middot; {{ $role->users_count }} staff assigned
                        </p>
                    </div>
                    <span class="rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 px-2.5 py-0.5 text-xs font-medium flex-shrink-0">
                        {{ count($role->permissions ?? []) }} permission{{ count($role->permissions ?? []) === 1 ? '' : 's' }}
                    </span>
                </div>
                <div class="mt-3 flex gap-2">
                    <button type="button" wire:click="startEdit({{ $role->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                        Edit
                    </button>
                    <button type="button" wire:click="delete({{ $role->id }})" wire:confirm="Delete this staff role? Staff must be reassigned first if any are currently using it." class="flex-1 rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                        Delete
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No custom staff roles yet - staff you create still default to Manager/Caretaker/Agent until you make one here.
            </div>
        @endforelse
    </div>
</div>
