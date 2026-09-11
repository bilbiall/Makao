<div class="space-y-4">
    @if (session('viewing-request-admitted'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('viewing-request-admitted') }}
        </div>
    @endif
    @if (session('viewing-request-revoked'))
        <div class="rounded-xl bg-slate-100 border border-slate-200 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
            {{ session('viewing-request-revoked') }}
        </div>
    @endif
    @if (session('viewing-request-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('viewing-request-error') }}
        </div>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-3 dark:bg-amber-500/10 dark:border-amber-500/20">
            <p class="text-[11px] text-amber-700 dark:text-amber-400">Pending</p>
            <p class="mt-1 text-lg font-bold text-amber-800 dark:text-amber-300">{{ $pendingCount }}</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-3 dark:bg-slate-900 dark:border-slate-800">
            <select wire:model.live="statusFilter" class="w-full h-full bg-transparent text-xs font-medium text-slate-600 dark:text-slate-300 focus:outline-none">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="admitted">Admitted</option>
                <option value="revoked">Revoked</option>
            </select>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-3 dark:bg-slate-900 dark:border-slate-800">
            <select wire:model.live="contactedFilter" class="w-full h-full bg-transparent text-xs font-medium text-slate-600 dark:text-slate-300 focus:outline-none">
                <option value="">Contacted: all</option>
                <option value="contacted">Contacted</option>
                <option value="not_contacted">Not contacted</option>
            </select>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-3 dark:bg-slate-900 dark:border-slate-800">
            <select wire:model.live="periodFilter" class="w-full h-full bg-transparent text-xs font-medium text-slate-600 dark:text-slate-300 focus:outline-none">
                <option value="">Sent: any time</option>
                <option value="today">Today</option>
                <option value="week">This week</option>
                <option value="month">This month</option>
            </select>
        </div>
        <div class="col-span-2 sm:col-span-4 rounded-2xl bg-white border border-slate-200 p-3 dark:bg-slate-900 dark:border-slate-800 flex items-center gap-2">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search name, phone, house"
                class="flex-1 h-full bg-transparent text-xs text-slate-600 dark:text-slate-300 placeholder:text-slate-400 focus:outline-none">
            <button type="button" wire:click="export" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-300" title="Export CSV">
                @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
            </button>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($requests as $request)
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $request->user?->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $request->user?->phone_number }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $request->house?->house_name ?? 'Unknown house' }} &middot; {{ $request->house?->location?->location_name }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Requested {{ $request->requested_at?->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-medium',
                            'bg-amber-100 text-amber-700' => $request->status === 'pending',
                            'bg-emerald-100 text-emerald-700' => $request->status === 'admitted',
                            'bg-rose-100 text-rose-700' => $request->status === 'revoked',
                        ])>{{ ucfirst($request->status) }}</span>
                        @if ($request->contacted_at)
                            <span class="text-[10px] text-emerald-600 dark:text-emerald-400" title="{{ $request->contacted_at->format('d M Y, H:i') }}">&check; Contacted</span>
                        @endif
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    @if ($request->user?->phone_number)
                        <a href="{{ \App\Support\PhoneNumber::toWhatsapp($request->user->phone_number) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 text-emerald-700 px-2.5 py-1.5 text-xs font-medium hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400">
                            @svg('heroicon-o-chat-bubble-left-right', 'w-3.5 h-3.5') WhatsApp
                        </a>
                        <a href="{{ \App\Support\PhoneNumber::toTel($request->user->phone_number) }}"
                            class="inline-flex items-center gap-1 rounded-lg bg-slate-100 text-slate-700 px-2.5 py-1.5 text-xs font-medium hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                            @svg('heroicon-o-phone', 'w-3.5 h-3.5') Call
                        </a>
                    @endif
                    @if ($request->user?->email)
                        <a href="mailto:{{ $request->user->email }}"
                            class="inline-flex items-center gap-1 rounded-lg bg-slate-100 text-slate-700 px-2.5 py-1.5 text-xs font-medium hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                            @svg('heroicon-o-envelope', 'w-3.5 h-3.5') Email
                        </a>
                    @endif
                    @if ($canAdmitViewingRequests)
                        <button wire:click="toggleContacted({{ $request->id }})"
                            class="ml-auto inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium {{ $request->contacted_at ? 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800' : 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900' }}">
                            {{ $request->contacted_at ? 'Mark not contacted' : 'Mark contacted' }}
                        </button>
                    @endif
                </div>

                @if ($request->status === 'pending')
                    @if ($activeActionId === $request->id && $activeAction === 'admit')
                        <div class="mt-3 rounded-xl bg-emerald-50 border border-emerald-100 p-3 space-y-2 dark:bg-emerald-500/10 dark:border-emerald-500/20">
                            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Confirm phone number</label>
                            <input type="text" wire:model="admitPhone" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('admitPhone') <p class="text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                            <p class="text-xs text-slate-500 dark:text-slate-400">This will promote {{ $request->user?->name }} to a tenant of {{ $request->house?->house_name }}.</p>
                            <div class="flex gap-2">
                                <button wire:click="cancelAction" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                                <button wire:click="confirmAdmit" class="flex-1 rounded-lg bg-emerald-600 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Confirm admit</button>
                            </div>
                        </div>
                    @elseif ($activeActionId === $request->id && $activeAction === 'revoke')
                        <div class="mt-3 rounded-xl bg-rose-50 border border-rose-100 p-3 space-y-2 dark:bg-rose-500/10 dark:border-rose-500/20">
                            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Reason (optional)</label>
                            <textarea wire:model="revokeNotes" rows="2" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                            <div class="flex gap-2">
                                <button wire:click="cancelAction" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-medium text-slate-700 dark:text-slate-300">Cancel</button>
                                <button wire:click="confirmRevoke" class="flex-1 rounded-lg bg-rose-600 py-2 text-xs font-semibold text-white hover:bg-rose-700">Confirm revoke</button>
                            </div>
                        </div>
                    @elseif ($canAdmitViewingRequests)
                        <div class="mt-3 flex gap-2">
                            <button wire:click="startRevoke({{ $request->id }})" class="flex-1 rounded-lg border border-rose-200 text-rose-700 py-2 text-xs font-semibold hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-400 dark:hover:bg-rose-500/10">Revoke</button>
                            <button wire:click="startAdmit({{ $request->id }})" class="flex-1 rounded-lg bg-emerald-600 text-white py-2 text-xs font-semibold hover:bg-emerald-700">Admit</button>
                        </div>
                    @endif
                @elseif ($request->admin_notes)
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Note: {{ $request->admin_notes }}</p>
                @endif
            </div>
        @empty
            <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                No viewing requests found.
            </div>
        @endforelse

        <div>{{ $requests->links() }}</div>
    </div>
</div>
