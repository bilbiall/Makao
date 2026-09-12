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

            @if ($booking->review)
                <div class="mt-6 border-t border-slate-100 pt-6">
                    <p class="text-sm font-semibold text-slate-900">Your review</p>
                    <p class="mt-1 flex items-center gap-0.5">
                        @for ($i = 1; $i <= 5; $i++)
                            @svg($i <= $booking->review->rating ? 'heroicon-s-star' : 'heroicon-o-star', 'w-4 h-4 ' . ($i <= $booking->review->rating ? 'text-amber-400' : 'text-slate-300'))
                        @endfor
                    </p>
                    @if ($booking->review->comment)
                        <p class="mt-2 text-sm text-slate-600">{{ $booking->review->comment }}</p>
                    @endif
                    <p class="mt-2 text-xs text-slate-400">Thanks for sharing your experience!</p>
                </div>
            @elseif ($booking->canBeReviewed())
                <form method="POST" action="{{ $reviewUrl }}" x-data="{ rating: 0, hover: 0 }" class="mt-6 space-y-3 border-t border-slate-100 pt-6">
                    @csrf
                    <p class="text-sm font-semibold text-slate-900">How was your stay?</p>
                    <div class="flex items-center gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0" aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
                                <svg x-show="(hover || rating) >= {{ $i }}" class="w-8 h-8 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>
                                <svg x-show="(hover || rating) < {{ $i }}" class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>
                            </button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" x-model="rating">
                    @error('rating') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    <textarea name="comment" rows="3" placeholder="Tell other guests about your stay (optional)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('comment') }}</textarea>
                    <button type="submit" x-bind:disabled="rating === 0" x-bind:class="rating === 0 ? 'opacity-50 cursor-not-allowed' : ''" class="w-full rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                        Submit review
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.marketing>
