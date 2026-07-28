<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\MpesaTransaction;
use App\Models\Invoice;
use App\Models\Payment;

class MpesaService
{
    protected $config;
    protected $consumerKey;
    protected $consumerSecret;
    protected $businessShortCode;
    protected $passkey;
    protected $sandbox = true;
    protected $callbackUrl;

    public function __construct()
    {
        $settings = \App\Models\Setting::singleton();
        $this->config = $settings->payload['mpesa'] ?? [];
        
        \Log::debug('MpesaService config loaded', [
            'has_payload' => isset($settings->payload),
            'has_mpesa_key' => isset($settings->payload['mpesa']),
            'mpesa_config_keys' => array_keys($this->config),
            'consumer_key_set' => !empty($this->config['consumer_key']),
            'consumer_secret_set' => !empty($this->config['consumer_secret']),
            'business_shortcode_set' => !empty($this->config['business_shortcode']),
            'passkey_set' => !empty($this->config['passkey']),
        ]);
        
        $this->consumerKey = $this->config['consumer_key'] ?? null;
        $this->consumerSecret = $this->config['consumer_secret'] ?? null;
        $this->businessShortCode = $this->config['business_shortcode'] ?? null;
        $this->passkey = $this->config['passkey'] ?? null;
        $this->sandbox = isset($this->config['sandbox']) ? (bool) $this->config['sandbox'] : true;
        $this->callbackUrl = $this->config['callback_url'] ?? (config('app.url') . '/api/mpesa/callback');
    }

    public function enabled(): bool
    {
        // When in sandbox, passkey may be intentionally left blank — allow it.
        $isEnabled = !empty($this->consumerKey) && !empty($this->consumerSecret)
            && !empty($this->businessShortCode) && ($this->sandbox ? true : !empty($this->passkey));
        
        if (!$isEnabled) {
            \Log::warning('M-Pesa not fully configured', [
                'consumer_key' => !empty($this->consumerKey) ? '✓' : '✗',
                'consumer_secret' => !empty($this->consumerSecret) ? '✓' : '✗',
                'business_shortcode' => !empty($this->businessShortCode) ? '✓' : '✗',
                'passkey' => !empty($this->passkey) ? '✓' : ($this->sandbox ? 'n/a(sandbox)' : '✗'),
                'sandbox' => $this->sandbox ? 'true' : 'false',
            ]);
        }
        
        return $isEnabled;
    }

    /**
     * Get OAuth access token from Daraja API
     */
    public function getAccessToken(): ?string
    {
        try {
            $auth = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
            $base = $this->sandbox ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $auth,
            ])
            ->timeout(10)
            ->get($base . '/oauth/v1/generate?grant_type=client_credentials');

            if ($response->ok()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }
            
            \Log::warning('M-Pesa token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            \Log::error('M-Pesa token exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Initiate STK Push for M-Pesa payment
     */
    public function initiateStkPush(Invoice $invoice, string $phoneNumber, float $amount): array
    {
        if (!$this->enabled()) {
            return ['success' => false, 'error' => 'M-Pesa not configured'];
        }

        // In live (non-sandbox) Daraja requires a publicly accessible HTTPS callback URL
        if (!$this->sandbox && !str_starts_with($this->callbackUrl, 'https://')) {
            \Log::error('M-Pesa invalid callback URL for live environment', ['callback' => $this->callbackUrl]);
            return ['success' => false, 'error' => 'Invalid callback URL: must be HTTPS in live mode'];
        }

        // Get access token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to obtain access token'];
        }

        // Create transaction record
        $reference = 'INV-' . $invoice->id . '-' . Str::random(8);

        // Determine house_id: prefer invoice->house_id, fall back to tenant->house_id
        $houseId = $invoice->house_id ?? null;
        if (empty($houseId) && $invoice->tenant) {
            $houseId = $invoice->tenant->house_id ?? null;
        }

        if (empty($houseId)) {
            \Log::error('M-Pesa transaction creation failed: missing house_id', [
                'invoice_id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id ?? null,
            ]);

            return ['success' => false, 'error' => 'Invoice missing house information'];
        }

        $transaction = MpesaTransaction::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'house_id' => $houseId,
            'amount' => $amount,
            'phone_number' => $this->formatPhoneNumber($phoneNumber),
            'reference' => $reference,
            'status' => 'pending',
            'meta' => [],
        ]);

        // Generate timestamp and password
        $timestamp = now()->format('YmdHis');
        // If passkey is missing in sandbox, use empty string — sandbox may not require it.
        $passkeyForPassword = $this->passkey ?? '';
        $password = base64_encode($this->businessShortCode . $passkeyForPassword . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $amount,
            'PartyA' => $this->formatPhoneNumber($phoneNumber),
            'PartyB' => $this->businessShortCode,
            'PhoneNumber' => $this->formatPhoneNumber($phoneNumber),
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $reference,
            'TransactionDesc' => 'Invoice ' . $invoice->invoice_number,
        ];

        try {
            \Log::info('M-Pesa STK push initiated', [
                'transaction_id' => $transaction->id,
                'reference' => $reference,
                'amount' => $amount,
            ]);

            $base = $this->sandbox ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';

            $response = Http::withToken($accessToken)
                ->asJson()
                ->timeout(10)
                ->post($base . '/mpesa/stkpush/v1/processrequest', $payload);

            $body = $response->json() ?? [];
            $rawBody = $response->body();

            \Log::debug('M-Pesa STK push response', [
                'status' => $response->status(),
                'body' => $body,
                'raw_body' => $rawBody,
            ]);

            $transaction->meta = array_merge($transaction->meta ?? [], [
                'stk_request' => $payload,
                'stk_response' => $body,
                'stk_raw_response' => $rawBody,
                'stk_status' => $response->status(),
            ]);

            if ($response->ok() && isset($body['CheckoutRequestID'])) {
                $transaction->checkout_request_id = $body['CheckoutRequestID'];
                $transaction->response_code = $body['ResponseCode'] ?? null;
                $transaction->response_message = $body['ResponseDescription'] ?? null;
                $transaction->save();

                return [
                    'success' => true,
                    'transaction' => $transaction,
                    'response' => $body,
                ];
            }

            $transaction->status = 'failed';
            $transaction->response_code = $body['errorCode'] ?? 'UNKNOWN';
            $transaction->response_message = $body['errorMessage'] ?? $rawBody;
            $transaction->save();

            \Log::error('M-Pesa STK push failed', [
                'transaction_id' => $transaction->id,
                'status' => $response->status(),
                'response' => $body,
            ]);

            return [
                'success' => false,
                'error' => $body['errorMessage'] ?? 'STK push failed',
                'transaction' => $transaction,
            ];
        } catch (\Throwable $e) {
            $transaction->status = 'failed';
            $transaction->response_message = $e->getMessage();
            $transaction->meta = array_merge($transaction->meta ?? [], [
                'exception' => $e->getMessage(),
            ]);
            $transaction->save();

            \Log::error('M-Pesa STK push exception', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'transaction' => $transaction,
            ];
        }
    }

    /**
     * Create the Payment record for a completed M-Pesa transaction, if one doesn't already
     * exist for it. Shared by the webhook callback and the status-poll fallback so a
     * payment is recorded exactly once no matter which path observes completion first.
     */
    protected function createPaymentIfMissing(MpesaTransaction $transaction, $paidAmount): ?Payment
    {
        $existing = Payment::where('transaction_id', $transaction->checkout_request_id)->first();
        if ($existing) {
            return $existing;
        }

        try {
            return Payment::create([
                'invoice_id' => $transaction->invoice_id,
                'tenant_id' => $transaction->tenant_id,
                'amount_paid' => $paidAmount,
                'payment_method' => 'mpesa',
                'payment_reference' => $transaction->receipt_number ?? $transaction->reference,
                'transaction_id' => $transaction->checkout_request_id,
                'status' => 'completed',
                'payment_date' => now(),
                'payment_type' => 'mpesa',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to create Payment record for M-Pesa transaction: ' . $e->getMessage(), ['transaction_id' => $transaction->id]);
            return null;
        }
    }

    /**
     * Query transaction status from M-Pesa
     */
    public function queryTransactionStatus(MpesaTransaction $transaction): array
    {
        // Already resolved by a webhook callback or a previous poll - nothing more to do.
        if ($transaction->status === 'completed') {
            return ['success' => true, 'status' => 'completed'];
        }

        if (!$this->enabled()) {
            return ['success' => false, 'error' => 'M-Pesa not configured'];
        }

        if (!$transaction->checkout_request_id) {
            return ['success' => false, 'error' => 'No checkout request ID'];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to obtain access token'];
        }

        $timestamp = now()->format('YmdHis');
        $passkeyForPassword = $this->passkey ?? '';
        $password = base64_encode($this->businessShortCode . $passkeyForPassword . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $transaction->checkout_request_id,
        ];

        try {
            $base = $this->sandbox ? 'https://sandbox.safaricom.co.ke' : 'https://api.safaricom.co.ke';

            \Log::info('M-Pesa status query sent', [
                'transaction_id' => $transaction->id,
                'checkout_request_id' => $transaction->checkout_request_id,
                'url' => $base . '/mpesa/stkpushquery/v1/query',
            ]);

            $response = Http::withToken($accessToken)
                ->asJson()
                ->timeout(10)
                ->post($base . '/mpesa/stkpushquery/v1/query', $payload);

            $body = $response->json() ?? [];

            \Log::debug('M-Pesa status query response', [
                'transaction_id' => $transaction->id,
                'status_code' => $response->status(),
                'body' => $body,
            ]);

            if ($response->ok()) {
                $resultCode = $body['ResultCode'] ?? null;
                
                // Handle both string and integer result codes
                $resultCodeInt = (int) $resultCode;
                
                if ($resultCodeInt === 0) {
                    // Success
                    $transaction->status = 'completed';
                    $transaction->result_code = (string) $resultCode;
                    $transaction->result_desc = $body['ResultDesc'] ?? null;
                    $transaction->receipt_number = $body['MerchantRequestID'] ?? null;
                    $transaction->save();

                    // The webhook callback may never arrive (unreachable callback URL, etc.) -
                    // make sure a Payment gets recorded here too, exactly once.
                    $this->createPaymentIfMissing($transaction, $transaction->amount);

                    try {
                        $invoice = $transaction->invoice;
                        $tenant = $transaction->tenant;
                        if ($invoice && $tenant) {
                            \App\Helpers\ActivityLogger::log('mpesa_payment', null, "M-Pesa payment of KES {$transaction->amount} confirmed via status query for Invoice {$invoice->invoice_number} (Tenant: {$tenant->tenant_name})");
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }

                    \Log::info('M-Pesa payment confirmed via status query', [
                        'transaction_id' => $transaction->id,
                        'result_code' => $resultCode,
                        'result_desc' => $body['ResultDesc'] ?? null,
                    ]);

                    return ['success' => true, 'status' => 'completed'];
                } elseif ($resultCodeInt === 1032) {
                    // Request timeout (not cancelled, just pending)
                    \Log::debug('M-Pesa query still pending', ['transaction_id' => $transaction->id]);
                    return ['success' => true, 'status' => 'pending'];
                } else {
                    // Failed or cancelled
                    $transaction->status = 'failed';
                    $transaction->result_code = (string) $resultCode;
                    $transaction->result_desc = $body['ResultDesc'] ?? null;
                    $transaction->save();
                    
                    \Log::warning('M-Pesa query returned failure', [
                        'transaction_id' => $transaction->id,
                        'result_code' => $resultCode,
                        'result_desc' => $body['ResultDesc'] ?? null,
                    ]);
                    
                    return ['success' => false, 'status' => 'failed', 'reason' => $body['ResultDesc'] ?? null];
                }
            }

            return ['success' => false, 'error' => $body['errorMessage'] ?? 'Query failed'];
        } catch (\Throwable $e) {
            \Log::error('M-Pesa query exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle callback from Safaricom
     */
    public function handleCallback(array $data): bool
    {
        try {
            $checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? null;
            $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;
            $resultDesc = $data['Body']['stkCallback']['ResultDesc'] ?? null;
            $callbackMetadata = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];

            if (!$checkoutRequestId) {
                \Log::warning('M-Pesa callback missing CheckoutRequestID');
                return false;
            }

            $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();
            if (!$transaction) {
                \Log::warning('M-Pesa transaction not found', ['checkout_request_id' => $checkoutRequestId]);
                return false;
            }

            // Idempotency: Safaricom (and webhook infrastructure generally) may deliver the
            // same callback more than once. If we've already recorded this as completed,
            // don't re-process it and double-credit the tenant.
            if ($transaction->status === 'completed') {
                \Log::info('M-Pesa callback received for an already-completed transaction, ignoring', [
                    'transaction_id' => $transaction->id,
                ]);
                return true;
            }

            $transaction->result_code = $resultCode;
            $transaction->result_desc = $resultDesc;

            \Log::info('M-Pesa callback received', [
                'transaction_id' => $transaction->id,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
            ]);

            if ($resultCode === 0) {
                // Payment successful
                $transaction->status = 'completed';
                
                // Extract metadata
                foreach ($callbackMetadata as $item) {
                    if ($item['Name'] === 'Amount') {
                        $transaction->meta = array_merge($transaction->meta ?? [], ['paid_amount' => $item['Value']]);
                    } elseif ($item['Name'] === 'MpesaReceiptNumber') {
                        $transaction->receipt_number = $item['Value'];
                    } elseif ($item['Name'] === 'PhoneNumber') {
                        $transaction->meta = array_merge($transaction->meta ?? [], ['paid_phone' => $item['Value']]);
                    }
                }

                $transaction->save();

                // Determine paid amount (prefer callback metadata if present)
                $paidAmount = $transaction->meta['paid_amount'] ?? $transaction->amount;

                // Ensure transaction amount reflects actual paid amount
                if ((float) $transaction->amount !== (float) $paidAmount) {
                    $transaction->amount = $paidAmount;
                    $transaction->save();
                }

                // Create Payment record (use Payment model's created hook to update invoice)
                $payment = $this->createPaymentIfMissing($transaction, $paidAmount);
                if (!$payment) {
                    return false;
                }

                // Note: no separate SMS send here - Payment::create() above already
                // triggers the model's `created` hook, which sends the payment
                // confirmation SMS via SmsHelper. Sending again here would double-SMS
                // the tenant and (previously) called a method that didn't exist,
                // which aborted this method before the activity log below ever ran.
                $tenant = $transaction->tenant;

                // Log M-Pesa payment
                try {
                    $invoice = $transaction->invoice;
                    \App\Helpers\ActivityLogger::log('mpesa_payment', null, "M-Pesa payment of KES {$paidAmount} received for Invoice {$invoice->invoice_number} (Tenant: {$tenant->tenant_name}, Receipt: {$transaction->receipt_number})");
                } catch (\Throwable $e) {
                    // ignore
                }

                \Log::info('M-Pesa payment completed', [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'receipt' => $transaction->receipt_number,
                ]);

                return true;
            } else {
                // Payment failed
                $transaction->status = 'failed';
                $transaction->save();

                \Log::warning('M-Pesa payment failed', [
                    'transaction_id' => $transaction->id,
                    'result_code' => $resultCode,
                    'description' => $resultDesc,
                ]);

                return false;
            }
        } catch (\Throwable $e) {
            \Log::error('M-Pesa callback exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format phone number to Safaricom format (254XXXXXXXXX)
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // If starts with 0, replace with 254
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }
        
        // If doesn't start with 254, add it
        if (!str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        }
        
        return $phone;
    }
}
