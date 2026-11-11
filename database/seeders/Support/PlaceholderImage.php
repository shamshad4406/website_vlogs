<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates local placeholder artwork for demo content.
 *
 * The launch plan calls out the dependency on an external placeholder image
 * service as something to remove, so these are drawn on the server with GD
 * and stored on the public disk like any real upload.
 */
class PlaceholderImage
{
    /** Candidate TrueType fonts, in preference order. */
    protected const FONTS = [
        'C:/Windows/Fonts/segoeuib.ttf',
        'C:/Windows/Fonts/arialbd.ttf',
        'C:/Windows/Fonts/arial.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
    ];

    /**
     * Draw a 16:9 thumbnail with a two-tone gradient and wrapped title text.
     *
     * @return string|null the path on the public disk
     */
    public static function thumbnail(
        string $title,
        string $label,
        string $hex,
        string $directory = 'thumbnails/demo',
        int $width = 1280,
        int $height = 720
    ): ?string {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor($width, $height);

        [$r, $g, $b] = self::rgb($hex);
        [$r2, $g2, $b2] = self::shift($r, $g, $b, -70);

        // Diagonal gradient.
        for ($y = 0; $y < $height; $y++) {
            $t = $y / max(1, $height - 1);
            $colour = imagecolorallocate(
                $image,
                (int) round($r + ($r2 - $r) * $t),
                (int) round($g + ($g2 - $g) * $t),
                (int) round($b + ($b2 - $b) * $t)
            );
            imagefilledrectangle($image, 0, $y, $width, $y, $colour);
        }

        // Soft geometric accents so cards do not look identical.
        $accent = imagecolorallocatealpha($image, 255, 255, 255, 110);
        imagefilledellipse($image, (int) ($width * 0.82), (int) ($height * 0.18), 420, 420, $accent);
        $accent2 = imagecolorallocatealpha($image, 0, 0, 0, 105);
        imagefilledellipse($image, (int) ($width * 0.1), (int) ($height * 0.95), 520, 520, $accent2);

        $white = imagecolorallocate($image, 255, 255, 255);
        $font = self::font();

        if ($font) {
            // Small uppercase category label.
            imagettftext($image, 22, 0, 64, 92, imagecolorallocatealpha($image, 255, 255, 255, 40), $font, mb_strtoupper($label));

            // Title in the upper half only: the lower third of a thumbnail is
            // covered by the duration pill and, on the home hero, by the
            // overlay that repeats the title.
            $size = mb_strlen($title) > 48 ? 44 : 54;
            $lines = array_slice(self::wrap($title, 26), 0, 2);
            $y = (int) ($height * 0.34);

            foreach ($lines as $line) {
                imagettftext($image, $size, 0, 64, (int) $y, $white, $font, $line);
                $y += $size + 16;
            }
        } else {
            // No TrueType font available: fall back to the bitmap font.
            imagestring($image, 5, 64, 70, strtoupper($label), $white);

            $y = (int) ($height / 2) - 30;

            foreach (self::wrap($title, 42) as $line) {
                imagestring($image, 5, 64, $y, $line, $white);
                $y += 28;
            }
        }

        Storage::disk('public')->makeDirectory($directory);
        $path = $directory.'/'.Str::slug(Str::limit($title, 40, '')).'-'.Str::random(6).'.jpg';

        ob_start();
        imagejpeg($image, null, 86);
        $binary = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /** A square avatar with initials, stored like an uploaded image. */
    public static function avatar(string $name, string $hex, string $directory = 'channels/demo', int $size = 400): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor($size, $size);
        [$r, $g, $b] = self::rgb($hex);
        imagefilledrectangle($image, 0, 0, $size, $size, imagecolorallocate($image, $r, $g, $b));

        $initials = collect(preg_split('/\s+/', $name) ?: [])
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');

        $white = imagecolorallocate($image, 255, 255, 255);
        $font = self::font();

        if ($font) {
            $box = imagettfbbox(150, 0, $font, $initials);
            $textWidth = abs($box[4] - $box[0]);
            imagettftext($image, 150, 0, (int) (($size - $textWidth) / 2), (int) ($size * 0.66), $white, $font, $initials);
        } else {
            imagestring($image, 5, (int) ($size / 2) - 10, (int) ($size / 2) - 8, $initials, $white);
        }

        Storage::disk('public')->makeDirectory($directory);
        $path = $directory.'/'.Str::slug($name).'-'.Str::random(5).'.jpg';

        ob_start();
        imagejpeg($image, null, 88);
        $binary = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /** A wide channel banner. */
    public static function banner(string $text, string $hex, string $directory = 'channels/demo'): ?string
    {
        return self::thumbnail($text, 'channel', $hex, $directory, 1600, 400);
    }

    protected static function font(): ?string
    {
        if (! function_exists('imagettftext')) {
            return null;
        }

        foreach (self::FONTS as $font) {
            if (is_file($font)) {
                return $font;
            }
        }

        return null;
    }

    /** @return array{0: int, 1: int, 2: int} */
    protected static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            $hex = '4338CA';
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /** @return array{0: int, 1: int, 2: int} */
    protected static function shift(int $r, int $g, int $b, int $by): array
    {
        return [
            max(0, min(255, $r + $by)),
            max(0, min(255, $g + $by)),
            max(0, min(255, $b + $by)),
        ];
    }

    /** @return array<int, string> */
    protected static function wrap(string $text, int $perLine): array
    {
        return array_slice(explode("\n", wordwrap($text, $perLine, "\n", true)), 0, 3);
    }
}
