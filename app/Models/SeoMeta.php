<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoMeta extends Model
{
    use HasFactory;

    protected $table = 'seo_meta';

    protected $fillable = [
        'page', 'title', 'description', 'keywords',
        'og_image', 'canonical_url', 'og_type', 'no_index',
    ];

    protected $casts = ['no_index' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('seo_meta'));
        static::deleted(fn () => Cache::forget('seo_meta'));
    }

    public static function forPage(string $page): ?self
    {
        $all = Cache::remember('seo_meta', 600, fn () => static::all()->keyBy('page'));

        return $all->get($page);
    }

    public function getOgImageUrlAttribute(): ?string
    {
        if (blank($this->og_image)) {
            return null;
        }

        return Str::startsWith($this->og_image, ['http://', 'https://'])
            ? $this->og_image
            : media_url($this->og_image);
    }
}
