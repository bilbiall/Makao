<?php

namespace App\Http\Controllers;

use App\Models\MpesaChannel;
use App\Services\MpesaC2bMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Safaricom C2B (Customer-to-Business) webhooks - called automatically whenever
 * anyone pays a registered Paybill directly from their M-Pesa app, i.e. without
 * ever touching this site's "Pay Now" (STK push) flow. Public, unauthenticated,
 * same as the STK callback in MpesaController - Safaricom identifies itself only
 * by calling the exact URL registered via MpesaChannelResource's "Register C2B"
 * action, there is no request signature to verify.
 */
class MpesaC2bController extends Controller
{
    /**
     * Validation URL - only ever called by Safaricom if a merchant has separately
     * asked Safaricom to enable "External" validation on their Paybill (most
     * haven't). Always accepts: real matching/reconciliation happens in
     * confirmation() below, which is called unconditionally after the money has
     * already moved and can't be rejected anyway.
     */
    public function validation(Request $request)
    {
        Log::info('M-Pesa C2B validation received', ['data' => $request->all()]);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Confirmation URL - called after a Paybill payment has already settled into
     * the landlord's own M-Pesa account. This endpoint only ever reads and records
     * that fact; it never moves money and can't undo it, so it always acknowledges
     * with ResultCode 0 regardless of match outcome (a non-zero/error response just
     * makes Safaricom retry the same confirmation).
     */
    public function confirmation(Request $request, MpesaC2bMatchService $matcher)
    {
        $payload = $request->all();

        Log::info('M-Pesa C2B confirmation received', [
            'business_shortcode' => $payload['BusinessShortCode'] ?? null,
            'trans_id' => $payload['TransID'] ?? null,
        ]);

        $shortcode = $payload['BusinessShortCode'] ?? null;
        $channel = $shortcode ? MpesaChannel::findByShortcode($shortcode) : null;

        if (!$channel) {
            Log::warning('M-Pesa C2B confirmation for unknown shortcode', ['business_shortcode' => $shortcode]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        try {
            $matcher->process($channel, $payload);
        } catch (\Throwable $e) {
            Log::error('M-Pesa C2B confirmation processing failed: ' . $e->getMessage(), ['payload' => $payload]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
