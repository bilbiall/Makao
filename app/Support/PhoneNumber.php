<?php

namespace App\Support;

/**
 * Normalizes Kenyan phone numbers stored in mixed formats (0712345678,
 * +254712345678, 254712345678) into the shapes wa.me and tel: links need.
 */
class PhoneNumber
{
    public static function toWhatsapp(?string $phone): ?string
    {
        $digits = self::toSafaricomFormat($phone);

        return $digits ? "https://wa.me/{$digits}" : null;
    }

    public static function toTel(?string $phone): ?string
    {
        $digits = self::toSafaricomFormat($phone);

        return $digits ? "tel:+{$digits}" : null;
    }

    public static function toSafaricomFormat(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '254' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '254')) {
            $digits = '254' . $digits;
        }

        return $digits;
    }
}
