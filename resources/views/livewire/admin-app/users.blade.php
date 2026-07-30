<div class="space-y-4">
    @if (session('user-created'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('user-created') }}
        </div>
    @endif

    <button wire:click="$set('showForm', true)" class="w-full rounded-xl bg-emerald-600 text-white text-sm font-semibold py-3 hover:bg-emerald-700 transition">
        + Add staff member
    </button>

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3">
            <div>
                <label class="text-xs font-medium text-slate-600">Name</label>
                <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Email</label>
                <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Password</label>
                <input type="password" wire:model="password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600">Role</label>
                <select wire:model.live="role" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="admin">Admin</option>
                    <option value="caretaker">Caretaker</option>
                </select>
            </div>
            @if ($role === 'caretaker')
                <div>
                    <label class="text-xs font-medium text-slate-600">Assigned location</label>
                    <select wire:model="location_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select location</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                        @endforeach
                    </select>
                    @error('location_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @endif
            <div class="flex gap-3">
                <button wire:click="$set('showForm', false)" class="flex-1 rounded-lg border border-slate-300 py-2.5 text-sm font-medium text-slate-700">Cancel</button>
                <button wire:click="create" class="flex-1 rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Create</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($staff as $member)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $member->name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $member->email }}</p>
                    @if ($member->location)
                        <p class="text-xs text-slate-400">{{ $member->location->location_name }}</p>
                    @endif
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-rose-100 text-rose-700' => $member->role === 'admin',
                    'bg-amber-100 text-amber-700' => $member->role === 'caretaker',
                ])>{{ ucfirst($member->role) }}</span>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
                No staff accounts yet.
            </div>
        @endforelse
    </div>
</div>
