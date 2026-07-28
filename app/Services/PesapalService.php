<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\PendingPayment;
use Illuminate\Support\Str;

class PesapalService
{
    protected $config;

    public function __construct()
    {
        $settings = \App\Models\Setting::singleton();
        $this->config = $settings->payload['pesapal'] ?? [];
    }

    public function enabled(): bool
    {
        return !empty($this->config['consumer_key']) && !empty($this->config['consumer_secret']);
    }

    /**
     * Create a pending payment with an external checkout URL when possible.
     * Returns array with keys: success (bool), redirect_url (string|null), pending (PendingPayment)
     */
    public function createPayment(PendingPayment $pending): array
    {
        // If not configured, return without creating external checkout
        if (!$this->enabled()) {
            return ['success' => false, 'redirect_url' => null, 'pending' => $pending];
        }

        // Example: API 3.0 uses OAuth2 / API key flows. We'll attempt a POST to the checkout endpoint.
        // The exact implementation depends on Pesapal API 3.0 contract; this is a conservative attempt
        // that may require adjustments for field names, signing, or OAuth token exchange.

        $sandbox = $this->config['sandbox'] ?? true;
        $base = $sandbox ? 'https://cybqa.pesapal.com/pesapalv3' : 'https://pay.pesapal.com/v3';
        $endpoint = $base . '/merchants/submit-order';

        // Build the callback URL dynamically based on environment
        $callbackUrl = config('app.url') . '/api/payments/pesapal/callback';
        $ipnUrl = config('app.url') . '/api/payments/pesapal/ipn';

        $payload = [
            'amount' => number_format($pending->amount, 2, '.', ''),
            'currency' => $this->config['currency'] ?? 'KES',
            'reference' => $pending->reference ?? \Illuminate\Support\Str::uuid()->toString(),
            'invoice_id' => $pending->invoice_id,
            'callback_url' => $this->config['callback_url'] ?? $callbackUrl,
            'notification_id' => $ipnUrl,
            'description' => 'Invoice ' . ($pending->invoice?->invoice_number ?? 'N/A'),
        ];

        // Add IPN ID if registered (required for Pesapal v3)
        if (!empty($this->config['ipn_id'])) {
            $payload['ipn_id'] = $this->config['ipn_id'];
        } else {
            \Log::warning('Pesapal IPN ID not configured in settings - checkout will likely fail', ['pending_id' => $pending->id]);
        }

        $requestAttempts = [];

        try {
            \Log::info('Pesapal: attempting basic auth checkout', ['endpoint' => $endpoint, 'pending_id' => $pending->id, 'payload' => $payload]);
            
            $resp = Http::withBasicAuth($this->config['consumer_key'], $this->config['consumer_secret'])
                ->timeout(10)
                ->post($endpoint, $payload);

            // store response and request for diagnostics
            // Capture raw body separately from JSON decode
            $rawBody = $resp->body();
            $body = $resp->json() ?? $rawBody;

            // Log raw response details
            \Log::info('Pesapal checkout response received', [
                'status' => $resp->status(),
                'headers' => $resp->headers(),
                'body_length' => strlen($rawBody),
                'body_empty' => empty($rawBody),
                'raw_body' => $rawBody,
                'pending_id' => $pending->id,
            ]);

            $requestAttempts[] = [
                'method' => 'basic_auth_checkout',
                'status' => $resp->status(),
                'response' => $body,
                'raw_body' => $rawBody,
                'body_empty' => empty($rawBody),
            ];

            $pending->meta = array_merge($pending->meta ?? [], [
                'pesapal_request' => $payload,
                'pesapal_response' => $body,
                'pesapal_raw_response' => $rawBody,
                'pesapal_status' => $resp->status(),
                'pesapal_attempts' => $requestAttempts,
            ]);
            $pending->save();

            if ($resp->ok()) {
                $decoded = is_array($body) ? $body : @json_decode($body, true);
                $url = $decoded['checkout_url'] ?? ($decoded['data']['checkout_url'] ?? null);
                if ($url) {
                    $pending->reference = $payload['reference'];
                    $pending->save();
                    return ['success' => true, 'redirect_url' => $url, 'pending' => $pending];
                } else {
                    \Log::warning('Pesapal returned 200 but no checkout_url in response', ['response' => $decoded, 'raw_body' => $rawBody, 'pending_id' => $pending->id]);
                }
            }

            // If unauthorized, attempt token flow below
            if ($resp->status() === 401) {
                \Log::warning('Pesapal initial checkout returned 401, attempting token flow', ['pending_id' => $pending->id]);
            } else {
                \Log::error('Pesapal checkout request failed', ['status' => $resp->status(), 'body' => $body, 'raw_body' => $rawBody, 'pending_id' => $pending->id]);
            }
        } catch (\Throwable $e) {
            \Log::error('Pesapal createPayment exception', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'pending_id' => $pending->id]);
            $requestAttempts[] = [
                'method' => 'basic_auth_checkout',
                'error' => $e->getMessage(),
            ];
            $pending->meta = array_merge($pending->meta ?? [], [
                'pesapal_error' => $e->getMessage(),
                'pesapal_attempts' => $requestAttempts,
            ]);
            $pending->save();
            // swallow and fallback to simulation
        }

        // Try token-based flow (common for APIs requiring OAuth2 client credentials)
        // Pesapal v3 uses the /oauth/token endpoint
        $tokenEndpoints = [
            $base . '/oauth/token',
        ];

        foreach ($tokenEndpoints as $tokenEndpoint) {
            try {
                \Log::info('Pesapal: attempting OAuth token request', ['token_endpoint' => $tokenEndpoint, 'pending_id' => $pending->id]);
                
                $tResp = Http::withBasicAuth($this->config['consumer_key'], $this->config['consumer_secret'])
                    ->timeout(10)
                    ->asForm()
                    ->post($tokenEndpoint, ['grant_type' => 'client_credentials']);

                $tBody = $tResp->json() ?? $tResp->body();
                
                $requestAttempts[] = [
                    'method' => 'token_request',
                    'endpoint' => $tokenEndpoint,
                    'status' => $tResp->status(),
                    'response' => $tBody,
                ];

                $pending->meta = array_merge($pending->meta ?? [], [
                    'pesapal_token_response' => $tBody,
                    'pesapal_token_status' => $tResp->status(),
                    'pesapal_attempts' => $requestAttempts,
                ]);
                $pending->save();

                if ($tResp->ok()) {
                    $decoded = is_array($tBody) ? $tBody : @json_decode($tBody, true);
                    $token = $decoded['access_token'] ?? $decoded['token'] ?? null;
                    if ($token) {
                        try {
                            \Log::info('Pesapal: attempting token-based checkout', ['pending_id' => $pending->id]);
                            
                            $checkoutResp = Http::withToken($token)->timeout(10)->post($endpoint, $payload);
                            $cBody = $checkoutResp->json() ?? $checkoutResp->body();
                            
                            $requestAttempts[] = [
                                'method' => 'token_checkout',
                                'status' => $checkoutResp->status(),
                                'response' => $cBody,
                            ];

                            $pending->meta = array_merge($pending->meta ?? [], [
                                'pesapal_token_checkout_response' => $cBody,
                                'pesapal_token_checkout_status' => $checkoutResp->status(),
                                'pesapal_attempts' => $requestAttempts,
                            ]);
                            $pending->save();

                            if ($checkoutResp->ok()) {
                                $decoded2 = is_array($cBody) ? $cBody : @json_decode($cBody, true);
                                $url = $decoded2['checkout_url'] ?? ($decoded2['data']['checkout_url'] ?? null);
                                if ($url) {
                                    $pending->reference = $payload['reference'];
                                    $pending->save();
                                    \Log::info('Pesapal: token checkout succeeded', ['pending_id' => $pending->id]);
                                    return ['success' => true, 'redirect_url' => $url, 'pending' => $pending];
                                } else {
                                    \Log::warning('Pesapal token checkout response missing checkout_url', ['response' => $cBody, 'pending_id' => $pending->id]);
                                }
                            } else {
                                \Log::error('Pesapal token checkout failed', ['status' => $checkoutResp->status(), 'body' => $cBody, 'pending_id' => $pending->id]);
                            }
                        } catch (\Throwable $e) {
                            \Log::error('Pesapal token checkout exception', ['message' => $e->getMessage(), 'pending_id' => $pending->id]);
                            $requestAttempts[] = [
                                'method' => 'token_checkout',
                                'error' => $e->getMessage(),
                            ];
                            $pending->meta = array_merge($pending->meta ?? [], [
                                'pesapal_error' => $e->getMessage(),
                                'pesapal_attempts' => $requestAttempts,
                            ]);
                            $pending->save();
                        }
                    } else {
                        \Log::warning('Pesapal token response missing access_token', ['response' => $tBody, 'pending_id' => $pending->id]);
                    }
                } else {
                    \Log::warning('Pesapal token request not OK', ['status' => $tResp->status(), 'body' => $tBody, 'pending_id' => $pending->id]);
                }
            } catch (\Throwable $e) {
                \Log::error('Pesapal token request exception', ['message' => $e->getMessage(), 'pending_id' => $pending->id]);
                $requestAttempts[] = [
                    'method' => 'token_request',
                    'error' => $e->getMessage(),
                ];
                $pending->meta = array_merge($pending->meta ?? [], [
                    'pesapal_error' => $e->getMessage(),
                    'pesapal_attempts' => $requestAttempts,
                ]);
                $pending->save();
            }
        }

        // Final fallback: ensure any error recorded
        if (empty($pending->meta['pesapal_error']) && empty($pending->meta['pesapal_response'])) {
            $pending->meta = array_merge($pending->meta ?? [], [
                'pesapal_error' => 'Unknown error during checkout request - all attempts failed',
                'pesapal_attempts' => $requestAttempts,
            ]);
            $pending->save();
            \Log::error('Pesapal: all checkout attempts failed with no specific error', ['pending_id' => $pending->id, 'attempts' => $requestAttempts]);
        }

        return ['success' => false, 'redirect_url' => null, 'pending' => $pending];
    }

    /**
     * Very small helper to verify a webhook signature. Expects header value and payload
     * Uses HMAC-SHA256 with the configured secret if present.
     */
    public function verifySignature(?string $signature, string $payload): bool
    {
        $secret = $this->config['webhook_secret'] ?? null;
        if (empty($secret) || empty($signature)) {
            return false;
        }
        $calc = hash_hmac('sha256', $payload, $secret);
        return hash_equals($calc, $signature);
    }
}
