<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Playlist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id', 'title', 'slug', 'description',
        'visibility', 'kind', 'items_count',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlaylistItem::class)->orderBy('position');
    }

    public function vlogs(): BelongsToMany
    {
        return $this->belongsToMany(Vlog::class, 'playlist_items')
            ->withPivot('position')
            ->orderBy('playlist_items.position');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', 'public');
    }

    public static function makeSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'playlist';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->vlogs()->first()?->thumbnail_url;
    }
}
