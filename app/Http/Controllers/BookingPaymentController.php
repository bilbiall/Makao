<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BnbMpesaService;
use Illuminate\Http\Request;

/**
 * Entirely separate route/controller from the existing /tenant/mpesa/* flow
 * (MpesaController) - no shared code path with tenant rent collection.
 */
class BookingPaymentController extends Controller
{
    public function initiate(Request $request, Booking $booking, BnbMpesaService $mpesa)
    {
        abort_if(in_array($booking->status, ['cancelled', 'checked_out']), 422);

        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1', 'max:' . $booking->total_amount],
        ]);

        $result = $mpesa->initiateStkPush($booking, $data['phone_number'], $data['amount']);

        if (!$result['success']) {
            return back()->withErrors(['payment' => $result['error'] ?? 'Payment could not be started.']);
        }

        return back()->with('status', 'Check your phone to complete the M-Pesa payment.');
    }

    public function callback(Request $request, BnbMpesaService $mpesa)
    {
        $mpesa->handleCallback($request->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
