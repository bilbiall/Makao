<x-layouts.marketing :title="'Your booking'">
    <div class="max-w-2xl mx-auto px-6 py-12">
        @if (session('status'))
            <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm p-4">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm p-4">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-6">
            <h1 class="text-xl font-bold text-slate-900">{{ $booking->house->house_name }}</h1>
            <p class="text-sm text-slate-500">{{ $booking->house->location?->location_name }}</p>

            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-slate-400">Check-in</p>
                    <p class="font-medium text-slate-900">{{ $booking->check_in->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-slate-400">Check-out</p>
                    <p class="font-medium text-slate-900">{{ $booking->check_out->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-slate-400">Guest</p>
                    <p class="font-medium text-slate-900">{{ $booking->guest_name }}</p>
                </div>
                <div>
                    <p class="text-slate-400">Total</p>
                    <p class="font-semibold text-emerald-700">KES {{ number_format($booking->total_amount) }}</p>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-amber-100 text-amber-700' => $booking->status === 'pending',
                    'bg-emerald-100 text-emerald-700' => in_array($booking->status, ['confirmed', 'checked_in']),
                    'bg-slate-100 text-slate-600' => in_array($booking->status, ['checked_out', 'cancelled']),
                ])>{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                @if ($booking->status === 'pending' && $booking->expires_at)
                    <span class="text-xs text-slate-400">Hold expires {{ $booking->expires_at->format('H:i') }}</span>
                @endif
            </div>

            @if ($booking->status === 'pending')
                <form method="POST" action="{{ $mpesaInitiateUrl }}" class="mt-6 space-y-3 border-t border-slate-100 pt-6">
                    @csrf
                    <p class="text-sm font-semibold text-slate-900">Pay with M-Pesa</p>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" name="phone_number" required placeholder="2547XXXXXXXX" value="{{ $booking->guest_phone }}"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <input type="number" name="amount" required max="{{ $booking->total_amount }}" value="{{ $booking->total_amount }}"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Amount (KES)">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                        Pay now
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.marketing>
