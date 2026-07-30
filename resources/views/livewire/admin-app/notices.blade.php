<div class="space-y-3">
    @forelse ($notices as $notice)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900">{{ $notice->tenant?->tenant_name ?? 'Unknown tenant' }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">Vacating {{ $notice->vacate_date->format('d M Y') }}</p>
                </div>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium flex-shrink-0',
                    'bg-amber-100 text-amber-700' => $notice->status === 'pending',
                    'bg-emerald-100 text-emerald-700' => $notice->status === 'approved',
                    'bg-rose-100 text-rose-700' => $notice->status === 'denied',
                ])>{{ ucfirst($notice->status) }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-600">{{ $notice->reason_type }}{{ $notice->reason_text ? ' - ' . $notice->reason_text : '' }}</p>

            @if ($notice->status === 'pending')
                <div class="mt-3 flex gap-2">
                    <button wire:click="deny({{ $notice->id }})" class="flex-1 rounded-lg border border-rose-300 text-rose-700 text-sm font-medium py-2">Deny</button>
                    <button wire:click="approve({{ $notice->id }})" class="flex-1 rounded-lg bg-emerald-600 text-white text-sm font-semibold py-2">Approve</button>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500">
            No notices submitted yet.
        </div>
    @endforelse
</div>
