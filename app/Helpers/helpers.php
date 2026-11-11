<?php

use App\Models\FeatureFlag;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    /**
     * Read a site setting, cached for the request.
     */
    function setting(string $key, $default = null)
    {
        return SiteSetting::get($key, $default);
    }
}

if (! function_exists('media_url')) {
    /**
     * Public URL for a file on the "public" disk.
     *
     * Uses asset(), so the URL is correct whether the app is served from a
     * domain root, from a sub-folder such as /website_vlog, or through
     * `php artisan serve`. Storage::url() cannot do that, because it is
     * pinned to APP_URL.
     */
    function media_url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}

if (! function_exists('seconds_to_clock')) {
    /**
     * 3725 => "1:02:05", 125 => "2:05".
     */
    function seconds_to_clock(?int $seconds): ?string
    {
        if (! $seconds) {
            return null;
        }

        return $seconds >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60)
            : sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}

if (! function_exists('short_number')) {
    /**
     * 1234 => "1.2K", 1500000 => "1.5M".
     */
    function short_number(int|float|null $value): string
    {
        $value = (float) $value;

        return match (true) {
            $value >= 1_000_000_000 => round($value / 1_000_000_000, 1).'B',
            $value >= 1_000_000 => round($value / 1_000_000, 1).'M',
            $value >= 1_000 => round($value / 1_000, 1).'K',
            default => (string) (int) $value,
        };
    }
}

if (! function_exists('bytes_to_human')) {
    function bytes_to_human(int|float|null $bytes): string
    {
        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}

if (! function_exists('feature_enabled')) {
    function feature_enabled(string $key, bool $default = false): bool
    {
        return FeatureFlag::enabled($key, $default);
    }
}
