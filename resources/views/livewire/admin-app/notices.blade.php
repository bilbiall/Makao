<div class="space-y-3">
    @if (session('notice-decided'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-3 py-2 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-300">
            {{ session('notice-decided') }}
        </div>
    @endif

    <div class="flex gap-2">
        <select wire:model.live="statusFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="denied">Denied</option>
        </select>
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search tenant or phone"
            class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder:text-slate-400">
        <button type="button" wire:click="export" class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
            @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
        </button>
    </div>

    @forelse ($notices as $notice)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $notice->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Vacating {{ $notice->vacate_date->format('d M Y') }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-amber-100 text-amber-700' => $notice->status === 'pending',
                    'bg-emerald-100 text-emerald-700' => $notice->status === 'approved',
                    'bg-rose-100 text-rose-700' => $notice->status === 'denied',
                ])>{{ ucfirst($notice->status) }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $notice->reason_type }}{{ $notice->reason_text ? ' - ' . $notice->reason_text : '' }}</p>
            @if ($notice->admin_notes)
                <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Note: {{ $notice->admin_notes }}</p>
            @endif

            @if ($notice->status === 'pending' && $canDecideNotices)
                <div class="mt-3 flex gap-2">
                    <button type="button" wire:click="startDeciding({{ $notice->id }}, 'deny')" class="flex-1 rounded-lg border border-rose-300 text-rose-700 text-sm font-medium py-2">Deny</button>
                    <button type="button" wire:click="startDeciding({{ $notice->id }}, 'approve')" class="flex-1 rounded-lg bg-emerald-600 text-white text-sm font-semibold py-2">Approve</button>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No notices found.
        </div>
    @endforelse

    <div>{{ $notices->links() }}</div>

    {{-- Approve/Deny confirmation with admin notes --}}
    @if ($decidingNoticeId)
        <div class="fixed inset-0 z-50">
            <div class="absolute inset-0 bg-slate-900/40" wire:click="cancelDeciding"></div>
            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center">
                <div class="bg-white rounded-t-2xl sm:rounded-2xl w-full sm:max-w-md p-4 shadow-xl dark:bg-slate-900">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">
                        {{ $decidingAction === 'approve' ? 'Approve notice' : 'Deny notice' }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        @if ($decidingAction === 'approve')
                            This ends the tenancy: the tenant is archived and the unit is freed up.
                        @else
                            The tenant keeps the unit and is notified their notice was denied.
                        @endif
                    </p>
                    <textarea wire:model="adminNotes" rows="3" placeholder="Notes (optional)"
                        class="mt-3 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 placeholder:text-slate-400"></textarea>
                    <div class="mt-3 flex gap-2">
                        <button type="button" wire:click="cancelDeciding" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium py-2">Cancel</button>
                        <button type="button" wire:click="confirmDecision"
                            @class([
                                'flex-1 rounded-lg text-white text-sm font-semibold py-2',
                                'bg-emerald-600' => $decidingAction === 'approve',
                                'bg-rose-600' => $decidingAction === 'deny',
                            ])>Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
