<div class="space-y-5">
    @if (session('profile-updated'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('profile-updated') }}
        </div>
    @endif
    @if (session('password-updated'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('password-updated') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 space-y-3">
        <p class="text-sm font-semibold text-slate-900">Your details</p>
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
            <label class="text-xs font-medium text-slate-600">Phone number</label>
            <input type="text" wire:model="phone_number" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <button wire:click="updateDetails" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save details</button>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 space-y-3">
        <p class="text-sm font-semibold text-slate-900">Change password</p>
        <div>
            <label class="text-xs font-medium text-slate-600">Current password</label>
            <input type="password" wire:model="current_password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @error('current_password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-medium text-slate-600">New password</label>
            <input type="password" wire:model="new_password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @error('new_password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-medium text-slate-600">Confirm new password</label>
            <input type="password" wire:model="new_password_confirmation" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <button wire:click="updatePassword" class="w-full rounded-lg border border-slate-300 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Update password</button>
    </div>
</div>
