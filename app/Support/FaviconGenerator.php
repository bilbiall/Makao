<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Regenerates public/favicon-32.png, public/apple-touch-icon.png, and
 * public/favicon.ico from whatever image the superadmin uploads as the site
 * logo. Always contain-fits the whole source image into a transparent square
 * canvas rather than cropping - a crop would need to guess where the "icon
 * part" of an arbitrary future logo ends, which isn't reliably detectable.
 */
class FaviconGenerator
{
    public static function generate(string $logoPublicDiskPath): void
    {
        $fullPath = Storage::disk('public')->path($logoPublicDiskPath);

        $source = @imagecreatefromstring(file_get_contents($fullPath));

        if (! $source) {
            return;
        }

        imagesavealpha($source, true);

        $favicon = self::containFit($source, 32);
        imagepng($favicon, public_path('favicon-32.png'));

        $touchIcon = self::containFit($source, 180);
        imagepng($touchIcon, public_path('apple-touch-icon.png'));

        self::writeIco(public_path('favicon-32.png'), public_path('favicon.ico'));

        imagedestroy($source);
        imagedestroy($favicon);
        imagedestroy($touchIcon);
    }

    protected static function containFit(\GdImage $source, int $target): \GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);

        $canvas = imagecreatetruecolor($target, $target);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);

        $scale = min($target / $w, $target / $h);
        $newW = (int) round($w * $scale);
        $newH = (int) round($h * $scale);
        $dstX = intdiv($target - $newW, 2);
        $dstY = intdiv($target - $newH, 2);

        imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $newW, $newH, $w, $h);

        return $canvas;
    }

    /** Wraps a 32x32 PNG in a minimal ICO container (PNG-in-ICO, supported since Windows Vista / all current browsers). */
    protected static function writeIco(string $pngPath, string $icoPath): void
    {
        $pngData = file_get_contents($pngPath);
        $header = pack('vvv', 0, 1, 1);
        $entry = pack('C4vvVV', 32, 32, 0, 0, 1, 32, strlen($pngData), 6 + 16);

        file_put_contents($icoPath, $header.$entry.$pngData);
    }
}
