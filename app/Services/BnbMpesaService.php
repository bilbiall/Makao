<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Booking;
use App\Models\BookingPayment;

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

    protected function loadConfigForLandlord(?int $landlordId): void
    {
        $settings = \App\Models\Setting::forLandlord($landlordId);
        $this->config = $settings->payload['mpesa'] ?? [];

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
        $this->loadConfigForLandlord($booking->landlord_id);

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
            $payment->meta = array_merge($payment->meta ?? [], ['stk_request' => $payload, 'stk_response' => $body]);

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

    public function handleCallback(array $data): bool
    {
        try {
            $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
            $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;

            if (!$checkoutRequestId) {
                return false;
            }

            $payment = BookingPayment::where('checkout_request_id', $checkoutRequestId)->first();
            if (!$payment || $payment->status === 'completed') {
                return (bool) $payment;
            }

            if ((int) $resultCode === 0) {
                $payment->markCompleted();

                return true;
            }

            $payment->update(['status' => 'failed']);
            return false;
        } catch (\Throwable $e) {
            \Log::error('BnB M-Pesa callback exception: ' . $e->getMessage());
            return false;
        }
    }
}
