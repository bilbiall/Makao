<div class="space-y-4">
    @if (session('mpesa-status'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            {{ session('mpesa-status') }}
        </div>
    @endif
    @if (session('mpesa-error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700 dark:bg-rose-500/10 dark:border-rose-500/20 dark:text-rose-400">
            {{ session('mpesa-error') }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-2">
        <x-admin.stat-tile label="Needs review" value="{{ $needsReviewCount }}" color="amber" />
        <x-admin.stat-tile label="Pending payments" value="{{ $pendingCount }}" color="sky" />
    </div>

    <div class="flex gap-2 text-sm font-medium">
        <button type="button" wire:click="switchTab('c2b')"
            @class([
                'flex-1 rounded-lg py-2 text-center',
                'bg-emerald-600 text-white' => $tab === 'c2b',
                'border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300' => $tab !== 'c2b',
            ])>C2B Payments</button>
        <button type="button" wire:click="switchTab('pending')"
            @class([
                'flex-1 rounded-lg py-2 text-center',
                'bg-emerald-600 text-white' => $tab === 'pending',
                'border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300' => $tab !== 'pending',
            ])>Pending Payments</button>
    </div>

    @if ($tab === 'c2b')
        <div class="flex gap-2">
            <select wire:model.live="c2bStatusFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <option value="">All statuses</option>
                <option value="needs_review">Needs review</option>
                <option value="matched_by_account">Matched (account)</option>
                <option value="matched_by_phone">Matched (phone)</option>
                <option value="manually_matched">Manually matched</option>
            </select>
            <button type="button" wire:click="exportC2b" class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
                @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
            </button>
        </div>

        <div class="space-y-3">
            @forelse ($c2bTransactions as $t)
                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $t->tenant?->tenant_name ?? 'Unmatched' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $t->channel?->label ?? $t->business_shortcode }} &middot; Acc: {{ $t->bill_ref_number ?: '(blank)' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $t->msisdn }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ optional($t->created_at)->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-slate-900 dark:text-slate-100">KES {{ number_format($t->trans_amount) }}</p>
                            <span @class([
                                'mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-amber-100 text-amber-700' => $t->match_status === 'needs_review',
                                'bg-emerald-100 text-emerald-700' => in_array($t->match_status, ['matched_by_account', 'matched_by_phone', 'manually_matched']),
                                'bg-slate-100 text-slate-600' => !in_array($t->match_status, ['needs_review', 'matched_by_account', 'matched_by_phone', 'manually_matched']),
                            ])>{{ str($t->match_status)->replace('_', ' ')->title() }}</span>
                        </div>
                    </div>

                    @if ($t->match_status === 'needs_review' && $canResolveMpesaReview)
                        @if ($assigningTransactionId === $t->id)
                            <div class="mt-3 flex gap-2">
                                <select wire:model="assigningTenantId" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="">Select tenant</option>
                                    @foreach ($assigningCandidates as $candidate)
                                        <option value="{{ $candidate->id }}">{{ $candidate->tenant_name }} ({{ $candidate->house?->publicName() }})</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="cancelAssign" class="rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-sm text-slate-600 dark:text-slate-300">Cancel</button>
                                <button type="button" wire:click="assignToTenant({{ $t->id }}, {{ $assigningTenantId ?: 0 }})" class="rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white">Assign</button>
                            </div>
                        @else
                            <div class="mt-3">
                                <button type="button" wire:click="startAssign({{ $t->id }})" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Assign to tenant
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            @empty
                <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                    No C2B transactions found.
                </div>
            @endforelse
        </div>

        <div>{{ $c2bTransactions->links() }}</div>
    @else
        <div class="flex gap-2">
            <select wire:model.live="pendingStatusFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
            </select>
            <button type="button" wire:click="exportPending" class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
                @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
            </button>
        </div>

        <div class="space-y-3">
            @forelse ($pendingPayments as $p)
                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $p->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $p->tenant?->house?->house_name }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">Ref: {{ $p->reference ?: '(none)' }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ optional($p->created_at)->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-slate-900 dark:text-slate-100">KES {{ number_format($p->amount) }}</p>
                            <span @class([
                                'mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-amber-100 text-amber-700' => $p->status === 'pending',
                                'bg-emerald-100 text-emerald-700' => $p->status === 'completed',
                                'bg-rose-100 text-rose-700' => $p->status === 'failed',
                            ])>{{ ucfirst($p->status) }}</span>
                        </div>
                    </div>

                    @if ($p->status === 'pending' && $canResolveMpesaReview)
                        <div class="mt-3 flex gap-2">
                            <button type="button" wire:click="markFailed({{ $p->id }})" wire:confirm="Mark this payment as failed?" class="flex-1 rounded-lg border border-rose-300 text-rose-700 text-sm font-medium py-2">Mark failed</button>
                            <button type="button" wire:click="markCompleted({{ $p->id }})" wire:confirm="Mark this payment as completed? This will record it against the tenant's invoice." class="flex-1 rounded-lg bg-emerald-600 text-white text-sm font-semibold py-2">Mark completed</button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
                    No pending payments found.
                </div>
            @endforelse
        </div>

        <div>{{ $pendingPayments->links() }}</div>
    @endif
</div>
