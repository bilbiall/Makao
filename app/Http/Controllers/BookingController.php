<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\House;
use App\Models\HousePricePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Create a pending booking (a time-boxed hold, not yet confirmed - confirmation
     * happens on payment, see BookingPaymentController). The lock+recheck here, not
     * the read-only availability filter on the search page, is what actually prevents
     * two guests both landing on the same overlapping dates.
     */
    public function store(Request $request, House $house)
    {
        abort_unless($house->isShortTerm(), 404);

        $data = $request->validate([
            'price_package_id' => ['required', 'exists:house_price_packages,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guest_name' => ['required_unless:use_account,1', 'nullable', 'string', 'max:255'],
            'guest_phone' => ['required_unless:use_account,1', 'nullable', 'string', 'max:30'],
            'guest_email' => ['nullable', 'email', 'max:255'],
        ]);

        $package = HousePricePackage::where('house_id', $house->id)->findOrFail($data['price_package_id']);

        $checkIn = \Illuminate\Support\Carbon::parse($data['check_in']);
        $checkOut = \Illuminate\Support\Carbon::parse($data['check_out']);
        $nights = max(1, $checkIn->diffInDays($checkOut));

        $user = Auth::user();
        $useAccount = $user && $user->isUser();

        $booking = DB::transaction(function () use ($house, $package, $checkIn, $checkOut, $nights, $user, $useAccount, $data) {
            // Lock the house row for the duration of this transaction so two
            // near-simultaneous requests for overlapping dates can't both pass the
            // overlap check before either commits.
            House::where('id', $house->id)->lockForUpdate()->first();

            $overlaps = Booking::where('house_id', $house->id)
                ->blocking()
                ->overlapping($checkIn, $checkOut)
                ->exists();

            if ($overlaps) {
                return null;
            }

            return Booking::create([
                'house_id' => $house->id,
                'user_id' => $useAccount ? $user->id : null,
                'price_package_id' => $package->id,
                'guest_name' => $useAccount ? $user->name : $data['guest_name'],
                'guest_phone' => $useAccount ? $user->phone_number : $data['guest_phone'],
                'guest_email' => $useAccount ? $user->email : ($data['guest_email'] ?? null),
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'package_name' => $package->name,
                'nightly_rate' => $package->price,
                'billing_unit' => $package->billing_unit,
                'total_amount' => $package->price * match ($package->billing_unit) {
                    'week' => (int) ceil($nights / 7),
                    'month' => (int) ceil($nights / 30),
                    default => $nights,
                },
                'status' => 'pending',
                'expires_at' => now()->addMinutes(20),
            ]);
        });

        if (!$booking) {
            return back()->withErrors(['booking' => 'Those dates were just booked by someone else. Please pick different dates.']);
        }

        return redirect()->route('bookings.show', $booking)
            ->with('status', 'Booking held for 20 minutes - complete payment to confirm.');
    }

    public function show(Booking $booking)
    {
        $booking->load('house.location', 'payments');

        return view('bookings.show', compact('booking'));
    }
}
