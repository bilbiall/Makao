<div class="space-y-5">
    @if (session('profile-updated'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('profile-updated') }}
        </div>
    @endif
    @if (session('password-updated'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('password-updated') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 space-y-3 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Your details</p>
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
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Phone number</label>
            <input type="text" wire:model="phone_number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
        </div>
        <button wire:click="updateDetails" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save details</button>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 space-y-3 dark:bg-slate-900 dark:border-slate-800">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Change password</p>
        <div>
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Current password</label>
            <input type="password" wire:model="current_password" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            @error('current_password') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">New password</label>
            <input type="password" wire:model="new_password" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            @error('new_password') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Confirm new password</label>
            <input type="password" wire:model="new_password_confirmation" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
        </div>
        <button wire:click="updatePassword" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Update password</button>
    </div>
</div>
