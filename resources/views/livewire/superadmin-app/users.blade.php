@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
@endphp
<div class="space-y-3">
    @if (session('user-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('user-saved') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search name, email, or phone..." class="{{ $inputClass }} mt-0">
        </div>
        <div>
            <select wire:model.live="roleFilter" class="{{ $inputClass }} mt-0">
                <option value="">All roles ({{ $roleCounts->sum() }})</option>
                @foreach ($roleCounts as $role => $count)
                    <option value="{{ $role }}">{{ ucfirst($role) }} ({{ $count }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="space-y-2">
        @forelse ($users as $user)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900 dark:text-slate-100 truncate">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $user->email }}{{ $user->phone_number ? ' · ' . $user->phone_number : '' }}</p>
                    @if ($user->landlord)
                        <p class="text-xs text-slate-400 dark:text-slate-500 truncate">{{ $user->landlord->name }}</p>
                    @endif
                    <div class="mt-2 flex gap-3">
                        <button type="button" wire:click="startEdit({{ $user->id }})" class="text-xs font-medium text-emerald-700 dark:text-emerald-400 hover:underline">Edit</button>
                        @if ($user->id !== auth()->id())
                            <button type="button" wire:click="delete({{ $user->id }})" wire:confirm="Delete {{ $user->name }}'s account? This cannot be undone." class="text-xs font-medium text-rose-600 hover:underline">Delete</button>
                        @endif
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <span class="rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 px-2.5 py-0.5 text-xs font-medium">
                        {{ $user->staffRole?->name ?? ucfirst($user->role) }}
                    </span>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Joined {{ $user->created_at->format('d M Y') }}</p>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No accounts match.
            </div>
        @endforelse
    </div>

    {{ $users->links() }}

    @if ($showForm)
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-900/40" wire:click="cancelForm"></div>
            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
                <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md max-h-[85vh] overflow-y-auto shadow-xl dark:bg-slate-900">
                    <div class="sticky top-0 bg-white border-b border-slate-200 p-4 flex items-center justify-between dark:bg-slate-900 dark:border-slate-800">
                        <p class="font-semibold text-slate-900 dark:text-slate-100">Edit account</p>
                        <button type="button" wire:click="cancelForm" class="p-1 text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-300" aria-label="Close">
                            @svg('heroicon-o-x-mark', 'w-5 h-5')
                        </button>
                    </div>

                    <form wire:submit="save" class="p-4 space-y-3">
                        <div>
                            <label class="{{ $labelClass }}">Name</label>
                            <input type="text" wire:model="name" class="{{ $inputClass }}">
                            @error('name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Email</label>
                            <input type="email" wire:model="email" class="{{ $inputClass }}">
                            @error('email') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Phone</label>
                            <input type="text" wire:model="phone_number" class="{{ $inputClass }}">
                            @error('phone_number') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Password (leave blank to keep current)</label>
                            <input type="password" wire:model="password" class="{{ $inputClass }}">
                            @error('password') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Role</label>
                            <select wire:model="role" class="{{ $inputClass }}">
                                @foreach (\App\Livewire\SuperadminApp\Users::ROLES as $roleOption)
                                    <option value="{{ $roleOption }}">{{ ucfirst($roleOption) }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Changing away from "Staff" clears any landlord-defined custom role this account had. A landlord-defined custom role itself isn't editable here - that stays on the landlord's own Staff Roles page.</p>
                            @error('role') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="button" wire:click="cancelForm" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                            <button type="submit" class="flex-1 rounded-lg bg-emerald-600 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
