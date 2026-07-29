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
        $settings = Setting::forLandlord($landlordId ?? CurrentLandlord::id());
        $payload = $settings->payload ?? [];

        $smsUrl = $payload['sms_url'] ?? env('TEXTSMS_URL');
        $smsApiKey = $payload['sms_api_key'] ?? env('TEXTSMS_API_KEY');
        $smsPartnerId = $payload['sms_partner_id'] ?? env('TEXTSMS_PARTNER_ID');
        $smsSenderId = $payload['sms_sender_id'] ?? env('TEXTSMS_SENDER_ID');

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
            $output = curl_error($ch);
        }

        curl_close($ch);

        return $output;
    }
}
