@php
    $inputClass = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100';
    $labelClass = 'text-xs font-medium text-slate-600 dark:text-slate-400';
    $isLocalUrl = str_contains(config('app.url'), 'localhost') || str_contains(config('app.url'), '127.0.0.1');
@endphp
<div class="space-y-3">
    @if (session('mpesa-saved'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('mpesa-saved') }}
        </div>
    @endif
    @if (session('mpesa-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('mpesa-error') }}
        </div>
    @endif

    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
        New here? Read the full <a href="{{ route('app.admin.mpesa-guide') }}" class="text-emerald-700 dark:text-emerald-400 underline font-semibold">M-Pesa Setup Guide</a> - where to get your Consumer Key/Secret/Passkey, what to enter below, and how to test STK push and C2B step by step.
    </div>

    @if ($isLocalUrl)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            <strong>Your site is on {{ config('app.url') }}</strong> - Safaricom cannot reach this to deliver an STK result or a C2B payment, so nothing will come back even with correct keys. See the "Local testing" section of the <a href="{{ route('app.admin.mpesa-guide') }}" class="underline font-semibold">M-Pesa Setup Guide</a>.
        </div>
    @endif

    @unless ($showForm)
        <button type="button" wire:click="startCreate" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            + Add channel
        </button>
    @endunless

    @if ($showForm)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 space-y-3 dark:bg-slate-900 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $editingId ? 'Edit channel' : 'Add channel' }}</p>

            <div>
                <label class="{{ $labelClass }}">Label</label>
                <input type="text" wire:model="label" placeholder="e.g. Kilimani Apartments Paybill" class="{{ $inputClass }}">
            </div>

            <div>
                <label class="{{ $labelClass }}">Applies to</label>
                <select wire:model="location_id" class="{{ $inputClass }}">
                    <option value="">All my properties (default channel)</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->location_name }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Leave blank to make this the default used by any property without its own channel.</p>
            </div>

            <div>
                <label class="{{ $labelClass }}">Paybill / Till Number</label>
                <input type="text" wire:model="business_shortcode" class="{{ $inputClass }}">
                @error('business_shortcode') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">Daraja app credentials</p>
            <p class="text-[11px] text-slate-400">The Consumer Key and Secret from your Daraja app - Safaricom uses this same pair for both STK push and C2B.</p>

            <div>
                <label class="{{ $labelClass }}">Daraja Consumer Key</label>
                <input type="text" wire:model="consumer_key" class="{{ $inputClass }}">
                @error('consumer_key') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $labelClass }}">Daraja Consumer Secret{{ $editingId ? ' (leave blank to keep current)' : '' }}</label>
                <input type="password" wire:model="consumer_secret" class="{{ $inputClass }}">
                @error('consumer_secret') <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" wire:model="sandbox" class="rounded border-slate-300 dark:border-slate-600">
                Use Sandbox (Daraja Test)
            </label>

            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">STK push ("Pay Now" button)</p>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" wire:model="stk_enabled" class="rounded border-slate-300 dark:border-slate-600">
                Use for "Pay Now" (STK push)
            </label>

            <div>
                <label class="{{ $labelClass }}">M-Pesa Online Passkey{{ $editingId ? ' (leave blank to keep current)' : '' }}</label>
                <input type="password" wire:model="passkey" class="{{ $inputClass }}">
                <p class="text-[11px] text-slate-400 mt-1">STK-only - needed in live mode, optional in sandbox.</p>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" wire:click="cancelForm" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-sm font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                <button type="button" wire:click="save" class="flex-1 rounded-lg bg-emerald-600 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
            </div>
        </div>
    @endif

    <div class="space-y-2">
        @forelse ($channels as $channel)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $channel->label ?: 'Unlabeled channel' }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $channel->location?->location_name ?? 'All properties (default)' }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Shortcode {{ $channel->business_shortcode }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        @if ($channel->sandbox)
                            <span class="rounded-full bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 px-2 py-0.5 text-xs font-medium">Sandbox</span>
                        @else
                            <span class="rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 px-2 py-0.5 text-xs font-medium">Live</span>
                        @endif
                        @if ($channel->stk_enabled)
                            <span class="text-[11px] text-slate-400">STK on</span>
                        @endif
                    </div>
                </div>

                @if ($c2bEnabled)
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        @if ($channel->c2b_registered_at)
                            C2B registered {{ $channel->c2b_registered_at->format('d M Y, H:i') }}
                        @else
                            C2B not yet registered
                        @endif
                    </p>
                @endif

                <div class="mt-3 flex gap-2">
                    <button type="button" wire:click="startEdit({{ $channel->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                        Edit
                    </button>
                    @if ($c2bEnabled)
                        <button type="button" wire:click="registerC2b({{ $channel->id }})" wire:confirm="This tells Safaricom to send Paybill payment confirmations for this shortcode to Renty. Continue?" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                            {{ $channel->c2b_registered_at ? 'Re-register C2B' : 'Register C2B' }}
                        </button>
                    @endif
                    <button type="button" wire:click="delete({{ $channel->id }})" wire:confirm="Delete this channel?" class="rounded-lg border border-rose-200 dark:border-rose-500/30 py-2 px-3 text-xs font-medium text-rose-600 dark:text-rose-400">
                        Delete
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No M-Pesa channels yet - add one above to start accepting payments.
            </div>
        @endforelse
    </div>
</div>
