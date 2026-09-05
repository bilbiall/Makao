<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\MpesaChannel;

/**
 * M-Pesa STK push for BnB booking payments. Deliberately independent of MpesaService
 * (which is hard-coupled to Invoice/Tenant) rather than a shared abstraction - this
 * duplicates a small amount of Daraja boilerplate (OAuth token, phone formatting) in
 * exchange for zero risk of ever touching the tenant rent-collection code path. See
 * the Phase 2 plan for why.
 */
class BnbMpesaService
{
    protected $config;
    protected $consumerKey;
    protected $consumerSecret;
    protected $businessShortCode;
    protected $passkey;
    protected $sandbox = true;
    protected $callbackUrl;

    /** See MpesaService::loadConfigForLocation() - identical resolution order
     *  (property-specific channel -> landlord's default channel -> legacy
     *  landlord-wide Setting payload), duplicated here rather than shared since
     *  this service is deliberately independent of MpesaService (see class docblock). */
    protected function loadConfigForLocation(?int $locationId, ?int $landlordId): void
    {
        $channel = MpesaChannel::resolveFor($locationId, $landlordId);

        if ($channel) {
            $this->config = [
                'consumer_key' => $channel->consumer_key,
                'consumer_secret' => $channel->consumer_secret,
                'business_shortcode' => $channel->business_shortcode,
                'passkey' => $channel->passkey,
                'sandbox' => $channel->sandbox,
            ];
        } else {
            $settings = \App\Models\Setting::forLandlord($landlordId);
            $this->config = $settings->payload['mpesa'] ?? [];
        }

        $this->consumerKey = $this->config['consumer_key'] ?? null;
        $this->consumerSecret = $this->config['consumer_secret'] ?? null;
        $this->businessShortCode = $this->config['business_shortcode'] ?? null;
        $this->passkey = $this->config['passkey'] ?? null;
        $this->sandbox = isset($this->config['sandbox']) ? (bool) $this->config['sandbox'] : true;
        $this->callbackUrl = $this->config['bnb_callback_url']
            ?? (config('app.url') . '/api/bookings/mpesa/callback');
    }

    public function enabled(): bool
    {
        return !empty($this->consumerKey) && !empty($this->consumerSecret)
            && !empty($this->businessShortCode) && ($this->sandbox ? true : !empty($this->passkey));
    }

    protected function getAccessToken(): ?string
    {
        try {
            $auth = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
            $base = $this->sandbox ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';

            $response = Http::withHeaders(['Authorization' => 'Basic ' . $auth])
                ->timeout(10)
                ->get($base . '/oauth/v1/generate?grant_type=client_credentials');

            return $response->ok() ? ($response->json()['access_token'] ?? null) : null;
        } catch (\Throwable $e) {
            \Log::error('BnB M-Pesa token exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    /**
     * Initiate STK push for a booking. $amount lets the guest pay a deposit or the
     * full total - BookingPayment.markCompleted() decides deposit_paid vs paid based
     * on what's actually been paid, not what was requested here.
     */
    public function initiateStkPush(Booking $booking, string $phoneNumber, float $amount): array
    {
        $this->loadConfigForLocation($booking->house?->location_id, $booking->landlord_id);

        if (!$this->enabled()) {
            return ['success' => false, 'error' => 'M-Pesa not configured'];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to obtain access token'];
        }

        $reference = 'BK-' . $booking->id . '-' . Str::random(8);
        $phone = $this->formatPhoneNumber($phoneNumber);

        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'method' => 'mpesa',
            'status' => 'pending',
            'phone_number' => $phone,
            'reference' => $reference,
            'meta' => [],
        ]);

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->businessShortCode . ($this->passkey ?? '') . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $amount,
            'PartyA' => $phone,
            'PartyB' => $this->businessShortCode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $reference,
            'TransactionDesc' => 'Booking #' . $booking->id,
        ];

        try {
            $base = $this->sandbox ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';

            $response = Http::withToken($accessToken)->asJson()->timeout(10)
                ->post($base . '/mpesa/stkpush/v1/processrequest', $payload);

            $body = $response->json() ?? [];
            $payment->meta = array_merge($payment->meta ?? [], [
                'stk_request' => array_merge($payload, ['Password' => '[redacted]']),
                'stk_response' => $body,
            ]);

            if ($response->ok() && isset($body['CheckoutRequestID'])) {
                $payment->checkout_request_id = $body['CheckoutRequestID'];
                $payment->save();

                return ['success' => true, 'payment' => $payment];
            }

            $payment->status = 'failed';
            $payment->save();

            return ['success' => false, 'error' => $body['errorMessage'] ?? 'STK push failed', 'payment' => $payment];
        } catch (\Throwable $e) {
            $payment->status = 'failed';
            $payment->meta = array_merge($payment->meta ?? [], ['exception' => $e->getMessage()]);
            $payment->save();

            \Log::error('BnB M-Pesa STK push exception: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage(), 'payment' => $payment];
        }
    }

    /**
     * SECURITY: same reasoning as MpesaService::handleCallback() - this endpoint is
     * public and unauthenticated with no Daraja-side signature to verify, so the
     * callback body's ResultCode is never trusted directly. It's only used to decide
     * whether to bother checking; the actual outcome always comes from an independent
     * queryTransactionStatus() call to Safaricom, crediting only the amount that was
     * originally requested when the STK push was initiated.
     */
    public function handleCallback(array $data): bool
    {
        try {
            $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;

            if (!$checkoutRequestId) {
                return false;
            }

            $payment = BookingPayment::where('checkout_request_id', $checkoutRequestId)->first();
            if (!$payment) {
                return false;
            }

            if ($payment->status === 'completed') {
                return true;
            }

            $result = $this->queryTransactionStatus($payment);

            return ($result['success'] ?? false) && ($result['status'] ?? null) === 'completed';
        } catch (\Throwable $e) {
            \Log::error('BnB M-Pesa callback exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Independently ask Safaricom what really happened to this STK push, rather than
     * trusting a client-supplied result. Mirrors MpesaService::queryTransactionStatus().
     */
    public function queryTransactionStatus(BookingPayment $payment): array
    {
        if ($payment->status === 'completed') {
            return ['success' => true, 'status' => 'completed'];
        }

        $this->loadConfigForLocation($payment->booking?->house?->location_id, $payment->landlord_id);

        if (!$this->enabled()) {
            return ['success' => false, 'error' => 'M-Pesa not configured'];
        }

        if (!$payment->checkout_request_id) {
            return ['success' => false, 'error' => 'No checkout request ID'];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to obtain access token'];
        }

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->businessShortCode . ($this->passkey ?? '') . $timestamp);

        $queryPayload = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $payment->checkout_request_id,
        ];

        try {
            $base = $this->sandbox ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';

            $response = Http::withToken($accessToken)->asJson()->timeout(10)
                ->post($base . '/mpesa/stkpushquery/v1/query', $queryPayload);

            $body = $response->json() ?? [];

            if ($response->ok()) {
                $resultCodeInt = (int) ($body['ResultCode'] ?? null);

                if ($resultCodeInt === 0) {
                    $payment->markCompleted();

                    return ['success' => true, 'status' => 'completed'];
                }

                if ($resultCodeInt === 1032) {
                    return ['success' => true, 'status' => 'pending'];
                }

                $payment->update(['status' => 'failed']);

                return ['success' => false, 'status' => 'failed', 'reason' => $body['ResultDesc'] ?? null];
            }

            return ['success' => false, 'error' => $body['errorMessage'] ?? 'Query failed'];
        } catch (\Throwable $e) {
            \Log::error('BnB M-Pesa query exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
