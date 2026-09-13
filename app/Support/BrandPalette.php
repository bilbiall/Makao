<?php

namespace App\Support;

use App\Models\Setting;
use Filament\Support\Colors\Color;

/**
 * The platform-wide brand color, set by the superadmin in Platform Settings >
 * Appearance. The app-shell/marketing side switches its whole `emerald-*` palette via
 * a CSS variable override keyed off `data-palette` on <html> (see resources/css/app.css) -
 * this class is the Filament-panel equivalent, since Filament's own color system is a
 * separate PHP-side mechanism (Color::X constants), not part of that CSS variable swap.
 */
class BrandPalette
{
    public const OPTIONS = [
        'green' => 'Green (default)',
        'blue' => 'Blue',
        'gold' => 'Gold',
        'red' => 'Red',
    ];

    /**
     * A specific landlord's own palette choice (Settings > General), falling back to
     * the platform-wide default (superadmin's Platform Settings > Appearance) when
     * that landlord hasn't picked one - same fallback semantics as every other
     * Setting::effective() field. Pass null (or omit) for the platform-wide palette
     * itself, e.g. on the public marketing site where no landlord is in play yet.
     */
    public static function current(?int $landlordId = null): string
    {
        $palette = Setting::effective($landlordId, 'brand_palette', 'green');

        return array_key_exists($palette, self::OPTIONS) ? $palette : 'green';
    }

    /** @return array<int, string> */
    public static function filamentColor(?string $palette = null, ?int $landlordId = null): array
    {
        return match ($palette ?? self::current($landlordId)) {
            'blue' => Color::Blue,
            'gold' => Color::Amber,
            'red' => Color::Red,
            default => Color::Emerald,
        };
    }
}
