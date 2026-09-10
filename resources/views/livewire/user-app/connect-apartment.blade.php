<div class="space-y-5">
    <div>
        <p class="text-xl font-bold text-slate-900 dark:text-slate-100">Connect your apartment</p>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Enter the code your property manager texted you, along with your phone number and unit name, to link your account.</p>
    </div>

    @if (session('connect-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('connect-error') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
        <div>
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Invite code</label>
            <input type="text" wire:model="code" placeholder="e.g. 7F3K9Q" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm uppercase tracking-widest dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            @error('code') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Phone number</label>
            <input type="text" wire:model="phone_number" placeholder="0712345678" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">The number your property manager has on file for you.</p>
            @error('phone_number') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Unit / house name</label>
            <input type="text" wire:model="house_name" placeholder="e.g. A1 or Studio 2" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Exactly as your property manager named your unit.</p>
            @error('house_name') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="button" wire:click="claim" wire:loading.attr="disabled" wire:target="claim"
            class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="claim">Connect</span>
            <span wire:loading wire:target="claim">Connecting...</span>
        </button>
    </div>

    <p class="text-xs text-slate-400 dark:text-slate-500">All three need to match exactly what your property manager entered - if anything fails to match, ask them to resend your invite.</p>
</div>
