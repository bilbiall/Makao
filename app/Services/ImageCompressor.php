<?php

namespace App\Services;

/**
 * Re-encodes an uploaded image to a capped dimension + JPEG quality before it's
 * stored, so a phone photo (often 4-8MB) becomes a few hundred KB instead -
 * important on shared hosting where storage/bandwidth is limited. Uses plain
 * GD (bundled with PHP almost everywhere, including shared cPanel hosts) -
 * deliberately no new Composer dependency for this.
 */
class ImageCompressor
{
    public function __construct(
        protected int $maxDimension = 1600,
        protected int $quality = 75,
    ) {
    }

    /**
     * Reads $sourcePath, compresses it, and writes the result to $destPath as a
     * JPEG (converting PNG/WebP/etc. too - a photo listing has no need for
     * transparency, and JPEG compresses far smaller for real photos).
     */
    public function compress(string $sourcePath, string $destPath): void
    {
        $info = @getimagesize($sourcePath);

        if (!$info) {
            // Not a readable image (or an SVG/corrupt file) - store as-is rather
            // than fail the whole upload over one bad file.
            copy($sourcePath, $destPath);

            return;
        }

        [$width, $height, $type] = $info;

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($sourcePath),
            default => null,
        };

        if (!$source) {
            copy($sourcePath, $destPath);

            return;
        }

        $scale = min(1, $this->maxDimension / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Flatten transparency onto white - a JPEG output has no alpha channel,
        // and a black background from an unflattened PNG looks broken.
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        imagejpeg($resized, $destPath, $this->quality);

        imagedestroy($source);
        imagedestroy($resized);
    }
}
