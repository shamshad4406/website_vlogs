<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    protected $fillable = ['key', 'label', 'description', 'enabled', 'rollout_percent'];

    protected $casts = ['enabled' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('feature_flags'));
        static::deleted(fn () => Cache::forget('feature_flags'));
    }

    public static function enabled(string $key, bool $default = false): bool
    {
        $flags = Cache::remember('feature_flags', 300, fn () => static::query()->pluck('enabled', 'key')->all());

        return array_key_exists($key, $flags) ? (bool) $flags[$key] : $default;
    }
}
