<?php

namespace App\Helpers;

use App\Models\Setting;
use App\Support\CurrentLandlord;
use RuntimeException;

class SmsHelper
{
    /**
     * $landlordId defaults to the current authenticated user's landlord when omitted
     * (covers Filament/web call sites); pass it explicitly from console commands and
     * webhook handlers, which have no authenticated user to infer it from.
     */
    public static function sendSms(string $phone, string $message, ?int $landlordId = null)
    {
        // Falls back to the platform-wide SMS gateway (Platform Settings), then the
        // .env default, so a landlord who hasn't set their own SMS credentials still
        // has notifications sent via a shared account.
        $resolvedLandlordId = $landlordId ?? CurrentLandlord::id();

        return self::sendWithConfig($phone, $message, [
            'sms_url' => Setting::effective($resolvedLandlordId, 'sms_url') ?? env('TEXTSMS_URL'),
            'sms_api_key' => Setting::effective($resolvedLandlordId, 'sms_api_key') ?? env('TEXTSMS_API_KEY'),
            'sms_partner_id' => Setting::effective($resolvedLandlordId, 'sms_partner_id') ?? env('TEXTSMS_PARTNER_ID'),
            'sms_sender_id' => Setting::effective($resolvedLandlordId, 'sms_sender_id') ?? env('TEXTSMS_SENDER_ID'),
        ]);
    }

    /**
     * Send using an explicit config rather than a landlord's stored settings - used by
     * the "Send test SMS" button in Settings, so whoever's editing the SMS tab can
     * verify the values they've just typed in actually work before saving them.
     */
    public static function sendWithConfig(string $phone, string $message, array $config)
    {
        // Trimmed defensively - a stray leading/trailing space from copy-pasting the
        // API key or Partner ID out of the TextSMS dashboard is invisible in a text
        // field but makes the gateway reject it as "Invalid credentials" (code 1006).
        $smsUrl = trim((string) ($config['sms_url'] ?? ''));
        $smsApiKey = trim((string) ($config['sms_api_key'] ?? ''));
        $smsPartnerId = trim((string) ($config['sms_partner_id'] ?? ''));
        $smsSenderId = trim((string) ($config['sms_sender_id'] ?? ''));

        if (
            empty($smsUrl) ||
            empty($smsApiKey) ||
            empty($smsPartnerId) ||
            empty($smsSenderId)
        ) {
            throw new RuntimeException('SMS settings are not configured.');
        }

        $postData = [
            'apikey'    => $smsApiKey,
            'partnerID' => $smsPartnerId,
            'message'   => $message,
            'shortcode' => $smsSenderId,
            'mobile'    => $phone,
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $smsUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $output = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException($error);
        }

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // TextSMS (textsms.co.ke) puts its real result in the JSON body regardless of
        // HTTP status ("respose-code" - their own typo, not ours) - check that FIRST,
        // before falling back to a generic HTTP-status error, so a 401/400 still gets
        // the gateway's own description (and the diagnostics below) instead of just
        // the raw status code.
        $decoded = json_decode((string) $output, true);
        $result = $decoded['responses'][0] ?? null;

        // What was actually transmitted, redacted - included in every failure below so
        // it's visible in the error itself, not just "invalid credentials", letting you
        // compare it character-for-character against the TextSMS dashboard instead of
        // guessing whether a value is empty, swapped, or subtly wrong.
        $diagnostics = sprintf(
            ' [sent: url=%s, partnerID="%s" (%d chars), apikey="%s" (%d chars), shortcode="%s"]',
            $smsUrl,
            $smsPartnerId,
            strlen($smsPartnerId),
            strlen($smsApiKey) > 8 ? substr($smsApiKey, 0, 4) . str_repeat('*', strlen($smsApiKey) - 8) . substr($smsApiKey, -4) : str_repeat('*', strlen($smsApiKey)),
            strlen($smsApiKey),
            $smsSenderId,
        );

        if ($result !== null) {
            $code = $result['respose-code'] ?? $result['response-code'] ?? null;

            if ((string) $code !== '200') {
                $description = $result['response-description'] ?? "SMS gateway rejected the message (code {$code}).";

                // Code 1006 is TextSMS's generic "the apikey/partnerID pair I received
                // doesn't match an account" - almost always a copy-paste slip rather
                // than a real account problem, so point at the likely fix directly.
                if ((string) $code === '1006') {
                    $description .= ' - double-check the API Key and Partner ID against your TextSMS.co.ke dashboard exactly (they are easy to swap with each other, and the Partner ID is a plain account number, not the API key).';
                }

                throw new RuntimeException($description . $diagnostics);
            }

            return $output;
        }

        if ($httpStatus >= 400) {
            throw new RuntimeException("SMS gateway returned HTTP {$httpStatus}: {$output}{$diagnostics}");
        }

        return $output;
    }
}
