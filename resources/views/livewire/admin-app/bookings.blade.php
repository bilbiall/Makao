<div class="space-y-3">
    @forelse ($bookings as $booking)
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $booking->house?->house_name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $booking->guest_name }} &middot; {{ $booking->guest_phone }}</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $booking->check_in->format('d M') }} - {{ $booking->check_out->format('d M Y') }} ({{ $booking->nights }} nights)</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-emerald-700 dark:text-emerald-400">KES {{ number_format($booking->total_amount) }}</p>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-[11px] font-medium',
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => in_array($booking->status, ['confirmed', 'checked_in']),
                        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => in_array($booking->status, ['checked_out', 'cancelled']),
                        'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' => $booking->status === 'pending',
                    ])>{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                </div>
            </div>
            @if ($booking->status === 'pending')
                <div class="mt-3 flex gap-2">
                    <button wire:click="confirm({{ $booking->id }})" class="flex-1 rounded-lg bg-emerald-600 py-2 text-xs font-semibold text-white">Confirm</button>
                    <button wire:click="cancel({{ $booking->id }})" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300">Cancel</button>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No bookings yet.
        </div>
    @endforelse

    {{ $bookings->links() }}
</div>
