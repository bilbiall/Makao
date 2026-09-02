<div class="space-y-4">
    @if (session('user-created'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('user-created') }}
        </div>
    @endif
    @if (session('user-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('user-error') }}
        </div>
    @endif

    <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
        + Add staff member
    </button>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Name</label>
                <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Email</label>
                <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Password</label>
                <input type="password" wire:model="password" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('password') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Role</label>
                <select wire:model.live="role" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    @if ($canAssignAdmin)
                        <option value="admin">Admin</option>
                    @endif
                    <option value="manager">Manager</option>
                    <option value="caretaker">Caretaker</option>
                    <option value="agent">Agent (BnB bookings)</option>
                </select>
            </div>
            @if (in_array($role, ['manager', 'caretaker']))
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Assigned properties</label>
                    <div class="mt-1 space-y-1.5 max-h-40 overflow-y-auto rounded-lg border border-slate-300 dark:border-slate-700 p-2">
                        @foreach ($locations as $location)
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="checkbox" wire:model="location_ids" value="{{ $location->id }}" class="rounded border-slate-300 dark:border-slate-600">
                                {{ $location->location_name }}
                            </label>
                        @endforeach
                    </div>
                    @error('location_ids') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            @endif
            @if ($role === 'agent')
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Assigned houses (short-stay only)</label>
                    <div class="mt-1 space-y-1.5 max-h-40 overflow-y-auto rounded-lg border border-slate-300 dark:border-slate-700 p-2">
                        @forelse ($shortTermHouses as $house)
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="checkbox" wire:model="house_ids" value="{{ $house->id }}" class="rounded border-slate-300 dark:border-slate-600">
                                {{ $house->house_name }}
                            </label>
                        @empty
                            <p class="text-xs text-slate-400 dark:text-slate-500">No short-stay houses yet - mark a house as BnB first.</p>
                        @endforelse
                    </div>
                    @error('house_ids') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>
            @endif
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button wire:click="create" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Create</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($staff as $member)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between dark:bg-slate-900 dark:border-slate-800">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $member->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $member->email }}</p>
                    @if ($member->assignedLocations->isNotEmpty())
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ $member->assignedLocations->pluck('location_name')->join(', ') }}</p>
                    @endif
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-rose-100 text-rose-700' => $member->role === 'admin',
                    'bg-indigo-100 text-indigo-700' => $member->role === 'manager',
                    'bg-amber-100 text-amber-700' => $member->role === 'caretaker',
                    'bg-emerald-100 text-emerald-700' => $member->role === 'agent',
                ])>{{ ucfirst($member->role) }}</span>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No staff accounts yet.
            </div>
        @endforelse
    </div>
</div>
