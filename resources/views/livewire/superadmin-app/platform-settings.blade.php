<div class="space-y-5">
    @if (session('platform-settings-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('platform-settings-saved') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 space-y-3">
        <p class="text-sm font-semibold text-slate-900">Analytics</p>
        <p class="text-xs text-slate-500">Applies to the public marketing site only, not the authenticated panels.</p>
        <div>
            <label class="text-xs font-medium text-slate-600">Google Analytics Measurement ID</label>
            <input type="text" wire:model="google_analytics_id" placeholder="G-XXXXXXXXXX" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @error('google_analytics_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 space-y-3">
        <p class="text-sm font-semibold text-slate-900">Support</p>
        <div>
            <label class="text-xs font-medium text-slate-600">Platform support email</label>
            <input type="email" wire:model="platform_support_email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @error('platform_support_email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <button wire:click="save" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
</div>
