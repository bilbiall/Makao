<?php

namespace App\Services;

use App\Helpers\AppHelper;
use App\Models\Setting;
use App\Support\ChatCopy;

/**
 * Direct-contact links the chat assistant offers when it can't fully help.
 * The numbers/email come from the superadmin's Platform Settings > Support
 * tab (support_whatsapp, support_phone, platform_support_email), so changing
 * them never needs a deploy. Only channels that are actually filled in are
 * offered.
 */
class SupportContactService
{
    /** @return list<array{kind: string, label: string, url: string}> */
    public function links(string $lang = 'en', array $filters = []): array
    {
        $payload = Setting::forLandlord(null)->payload ?? [];
        $links = [];

        $message = ChatCopy::t('handoff_message', $lang, ['app' => AppHelper::getAppName(null)]);
        if ($summary = ChatCopy::summary($filters, $lang)) {
            $message .= ChatCopy::t('handoff_looking_for', $lang, ['summary' => $summary]);
        }

        if ($whatsapp = $this->internationalDigits($payload['support_whatsapp'] ?? null)) {
            $links[] = [
                'kind' => 'whatsapp',
                'label' => ChatCopy::t('link_whatsapp', $lang),
                'url' => "https://wa.me/{$whatsapp}?text=".rawurlencode($message),
            ];
        }

        if ($phone = $this->internationalDigits($payload['support_phone'] ?? null)) {
            $links[] = ['kind' => 'call', 'label' => ChatCopy::t('link_call', $lang), 'url' => "tel:+{$phone}"];
        }

        $email = trim((string) ($payload['platform_support_email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $links[] = [
                'kind' => 'email',
                'label' => ChatCopy::t('link_email', $lang),
                'url' => 'mailto:'.$email.'?subject='.rawurlencode(AppHelper::getAppName(null).' - '.($lang === 'sw' ? 'Msaada' : 'Help')).'&body='.rawurlencode($message),
            ];
        }

        return $links;
    }

    /**
     * "0712 345 678", "+254712345678" and "712345678" all become 254712345678 -
     * the format wa.me and tel: links need. Anything that doesn't look like a
     * real phone number is dropped rather than turned into a dead link.
     */
    protected function internationalDigits(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if ($digits === '') {
            return null;
        }

        $digits = match (true) {
            str_starts_with($digits, '254') => $digits,
            str_starts_with($digits, '0') => '254'.substr($digits, 1),
            strlen($digits) === 9 => '254'.$digits,
            default => $digits,
        };

        return strlen($digits) >= 11 && strlen($digits) <= 15 ? $digits : null;
    }
}
