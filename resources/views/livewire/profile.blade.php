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

        <div class="flex items-center gap-4">
            <div class="relative flex-shrink-0">
                @if ($new_avatar)
                    <img src="{{ $new_avatar->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                @elseif (auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}" class="w-16 h-16 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                @else
                    <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center text-lg font-semibold text-emerald-700 dark:text-emerald-400">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div>
                <label class="inline-block cursor-pointer rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                    Change photo
                    <input type="file" wire:model="new_avatar" accept="image/*" class="hidden">
                </label>
                <p class="text-[11px] text-slate-400 mt-1" wire:loading wire:target="new_avatar">Uploading...</p>
                @error('new_avatar') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

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
        @if ($this->hasPublicProfile())
            <div>
                <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Bio</label>
                <textarea wire:model="bio" rows="3" placeholder="Tell guests a bit about yourself and how you can help them..." class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                @error('bio') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
        @endif
        <button wire:click="updateDetails" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save details</button>
    </div>

    @if ($this->hasPublicProfile())
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Your public profile</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Share this link with guests - it shows your photo, bio, rating, and every stay you manage.</p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ $this->publicProfileUrl() }}" x-ref="profileUrl" class="flex-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400" onclick="this.select()">
                <a href="{{ $this->publicProfileUrl() }}" target="_blank" class="flex-shrink-0 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">View</a>
            </div>
        </div>
    @endif

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
