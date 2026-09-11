@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
@endphp
<div class="space-y-3">
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
</div>
