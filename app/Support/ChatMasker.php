<?php

namespace App\Support;

/**
 * Strips personal contact details from a chat message before it is stored for
 * the superadmin insights dashboard. Budgets ("10k", "KES 14,000") are
 * deliberately left alone - they're exactly what the dashboard needs.
 */
class ChatMasker
{
    public static function mask(string $text): string
    {
        // Emails first, so the digits inside one aren't half-masked as a phone.
        $text = preg_replace('/[\w.+-]+@[\w-]+(?:\.[\w-]+)+/u', '[email]', $text);

        // Kenyan and international phone numbers, with or without separators.
        $text = preg_replace('/(?<!\d)(?:\+?254|0)[\s-]?[17]\d{2}[\s-]?\d{3}[\s-]?\d{3}(?!\d)/', '[phone]', $text);

        // Any other long digit run (ID/passport numbers, card numbers). A rent
        // figure never has this many digits.
        $text = preg_replace('/(?<!\d)\d[\d\s-]{6,}\d(?!\d)/', '[number]', $text);

        return $text;
    }
}
