<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CreatorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel_name',
        'bio',
        'about',
        'location',
        'profile_image',
        'banner_image',
        'featured_vlog_id',
        'trailer_vlog_id',
        'website_url',
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_youtube',
        'subscribers_count',
        'total_views',
        'is_verified',
        'strike_count',
        'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'subscribers_count' => 'integer',
        'total_views' => 'integer',
        'strike_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function featuredVlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class, 'featured_vlog_id');
    }

    public function trailerVlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class, 'trailer_vlog_id');
    }

    protected function publicUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : media_url($path);
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->publicUrl($this->banner_image);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->publicUrl($this->profile_image);
    }
}
