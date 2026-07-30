<div class="space-y-5">
    @if (session('settings-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('settings-saved') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 space-y-3">
        <p class="text-sm font-semibold text-slate-900">Collection method</p>
        <p class="text-xs text-slate-500">Choose how rent payments are collected from tenants.</p>
        <div class="space-y-2">
            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer">
                <input type="radio" wire:model="payment_mode" value="manual" class="mt-1">
                <span>
                    <span class="block text-sm font-medium text-slate-800">Manual</span>
                    <span class="block text-xs text-slate-500">Tenants pay you directly (cash, bank, M-Pesa till) and you record it yourself.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer">
                <input type="radio" wire:model="payment_mode" value="automatic" class="mt-1">
                <span>
                    <span class="block text-sm font-medium text-slate-800">Automatic</span>
                    <span class="block text-xs text-slate-500">Tenants pay in-app via M-Pesa STK push / Pesapal using your credentials.</span>
                </span>
            </label>
        </div>
        <button wire:click="save" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
    </div>

    <a href="{{ url('/admin/settings') }}" class="block text-center text-sm font-medium text-emerald-700">
        Advanced settings (SMS, Email, M-Pesa/Pesapal credentials, templates) &rarr;
    </a>
</div>
