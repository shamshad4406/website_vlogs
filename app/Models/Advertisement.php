<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Advertisement extends Model
{
    use HasFactory, SoftDeletes;

    public const POSITIONS = ['homepage', 'sidebar', 'video_page', 'category_page'];

    protected $fillable = [
        'campaign_id',
        'title',
        'alt_text',
        'image',
        'link',
        'position',
        'weight',
        'status',
        'start_date',
        'end_date',
        'impressions_count',
        'clicks_count',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'weight' => 'integer',
        'impressions_count' => 'integer',
        'clicks_count' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AdClick::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function (Builder $q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function scopeAtPosition(Builder $query, string $position): Builder
    {
        return $query->where('position', $position);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return Str::startsWith($this->image, ['http://', 'https://'])
            ? $this->image
            : media_url($this->image);
    }

    public function getCtrAttribute(): float
    {
        return $this->impressions_count > 0
            ? round($this->clicks_count / $this->impressions_count * 100, 2)
            : 0.0;
    }
}
