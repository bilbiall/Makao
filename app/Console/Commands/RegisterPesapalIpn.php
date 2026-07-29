<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class RegisterPesapalIpn extends Command
{
    protected $signature = 'pesapal:register-ipn {--landlord= : Landlord ID whose Pesapal credentials to use (each landlord has their own)}';
    protected $description = 'Register Pesapal IPN URL and retrieve the IPN ID (required for v3 checkout)';

    public function handle()
    {
        $this->info('Pesapal IPN Registration Helper');
        $this->info('================================\n');

        $landlordId = $this->option('landlord');
        if (!$landlordId) {
            $this->error('❌ Pesapal credentials are per-landlord. Pass --landlord=<id>.');
            return 1;
        }

        $settings = Setting::forLandlord((int) $landlordId);
        $pesapalConfig = $settings->payload['pesapal'] ?? [];

        // Check prerequisites
        if (empty($pesapalConfig['consumer_key']) || empty($pesapalConfig['consumer_secret'])) {
            $this->error('❌ Consumer Key and Consumer Secret not configured. Please set them in that landlord\'s Admin Settings first.');
            return 1;
        }

        $sandbox = $pesapalConfig['sandbox'] ?? true;
        $base = $sandbox ? 'https://cybqa.pesapal.com/pesapalv3' : 'https://pay.pesapal.com/v3';
        $environment = $sandbox ? 'SANDBOX' : 'LIVE';

        $this->info("Environment: {$environment}");
        $this->info("Base URL: {$base}\n");

        // Step 1: Get OAuth token
        $this->info('Step 1/3: Requesting OAuth token...');
        try {
            $tokenEndpoint = $base . '/oauth/token';
            $this->info('TOKEN URL: ' . $tokenEndpoint);
            $tokenResp = Http::asJson()
                ->acceptJson()
                ->timeout(15)
                ->post($tokenEndpoint, [
                    'consumer_key' => $pesapalConfig['consumer_key'],
                    'consumer_secret' => $pesapalConfig['consumer_secret'],
                ]);

            if (!$tokenResp->ok()) {
                $this->error("❌ Token request failed: {$tokenResp->status()}");
                $this->error("Response: " . $tokenResp->body());
                return 1;
            }

            $tokenBody = $tokenResp->json();
            $accessToken = $tokenBody['access_token'] ?? null;
            if (!$accessToken) {
                $this->error('❌ Token response missing access_token field');
                $this->error('Full Response: ' . json_encode($tokenBody, JSON_PRETTY_PRINT));
                $this->error('Status: ' . $tokenResp->status());
                $this->error('Raw Body: ' . $tokenResp->body());
                return 1;
            }

            $this->info("✓ Token received\n");
        } catch (\Throwable $e) {
            $this->error("❌ Token request exception: {$e->getMessage()}");
            return 1;
        }

        // Step 2: Register IPN URL
        $this->info('Step 2/3: Registering IPN URL...');
        
        $callbackUrl = config('app.url') . '/api/payments/pesapal/ipn';
        $this->info("IPN URL: {$callbackUrl}");

        try {
            $notificationEndpoint = $base . '/notification-urls';
            $this->info('NOTIFICATION URL: ' . $notificationEndpoint);
            $notificationResp = Http::withToken($accessToken)
                ->asJson()
                ->acceptJson()
                ->timeout(10)
                ->post($notificationEndpoint, [
                    'url' => $callbackUrl,
                    'ipn_notification_type' => 'POST',
                ]);

            if (!$notificationResp->ok()) {
                $this->error("❌ IPN registration failed: {$notificationResp->status()}");
                $this->error("Response: " . $notificationResp->body());
                return 1;
            }

            $notificationBody = $notificationResp->json();
            $ipnId = $notificationBody['ipn_id'] ?? null;

            if (!$ipnId) {
                $this->error('❌ IPN response missing ipn_id field');
                $this->error('Response: ' . json_encode($notificationBody));
                return 1;
            }

            $this->info("✓ IPN registered\n");
        } catch (\Throwable $e) {
            $this->error("❌ IPN registration exception: {$e->getMessage()}");
            return 1;
        }

        // Step 3: Save IPN ID to settings
        $this->info('Step 3/3: Saving IPN ID to settings...');

        $pesapalConfig['ipn_id'] = $ipnId;
        $settings->payload = array_merge($settings->payload ?? [], ['pesapal' => $pesapalConfig]);
        $settings->save();

        $this->info("✓ Settings saved\n");

        $this->info('================================');
        $this->info('✅ SUCCESS');
        $this->info("IPN ID: {$ipnId}");
        $this->info("Environment: {$environment}");
        $this->info('================================');
        $this->info("\nThis IPN ID has been saved to your settings and will be used for all Pesapal v3 checkout requests.");

        return 0;
    }
}
