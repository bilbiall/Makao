<div class="space-y-3">
    <div class="flex gap-2">
        <select wire:model.live="houseFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <option value="">All houses</option>
            @foreach ($houses as $house)
                <option value="{{ $house->id }}">{{ $house->house_name }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="checked_in">Checked in</option>
            <option value="checked_out">Checked out</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <button type="button" wire:click="export" class="flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800" title="Export CSV">
            @svg('heroicon-o-arrow-down-tray', 'w-5 h-5')
        </button>
    </div>

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
                    @if ($booking->payment_status)
                        <span @class([
                            'block mt-1 rounded-full px-2 py-0.5 text-[11px] font-medium',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $booking->payment_status === 'paid',
                            'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400' => $booking->payment_status === 'deposit_paid',
                            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => $booking->payment_status === 'refunded',
                            'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' => $booking->payment_status === 'unpaid',
                        ])>{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span>
                    @endif
                </div>
            </div>

            <div class="mt-3 flex gap-2">
                @if ($canManageBookings && $booking->status === 'pending')
                    <button wire:click="confirm({{ $booking->id }})" wire:confirm="Confirm this booking?" class="flex-1 rounded-lg bg-emerald-600 py-2 text-xs font-semibold text-white">Confirm</button>
                @endif
                @if ($canManageBookings && $booking->status === 'confirmed')
                    <button wire:click="check_in({{ $booking->id }})" class="flex-1 rounded-lg bg-sky-600 py-2 text-xs font-semibold text-white">Check in</button>
                @endif
                @if ($canManageBookings && $booking->status === 'checked_in')
                    <button wire:click="check_out({{ $booking->id }})" class="flex-1 rounded-lg bg-slate-700 py-2 text-xs font-semibold text-white">Check out</button>
                @endif
                @if ($canManageBookings && !in_array($booking->status, ['checked_out', 'cancelled']))
                    <button wire:click="cancel({{ $booking->id }})" wire:confirm="Cancel this booking?" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-700 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300">Cancel</button>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center text-sm text-slate-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-400">
            No bookings yet.
        </div>
    @endforelse

    {{ $bookings->links() }}
</div>
