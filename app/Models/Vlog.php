<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vlog extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['draft', 'pending', 'published', 'rejected', 'unpublished'];

    public const VISIBILITIES = ['public', 'unlisted', 'private'];

    protected $fillable = [
        'creator_id',
        'category_id',
        'title',
        'slug',
        'description',
        'video_url',
        'video_file',
        'processing_status',
        'processing_error',
        'hls_path',
        'thumbnail',
        'preview_sprite',
        'duration',
        'duration_seconds',
        'location',
        'language',
        'visibility',
        'allow_comments',
        'status',
        'rejection_reason',
        'is_featured',
        'is_trending',
        'published_at',
        'scheduled_at',
        'views_count',
        'likes_count',
        'comments_count',
        'watch_seconds',
        'shares_count',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'allow_comments' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'watch_seconds' => 'integer',
        'shares_count' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /* ---------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'vlog_tags');
    }

    public function views(): HasMany
    {
        return $this->hasMany(VlogView::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedRootComments(): HasMany
    {
        return $this->hasMany(Comment::class)
            ->whereNull('parent_id')
            ->where('is_approved', true);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(VlogReport::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(VideoAsset::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(VlogChapter::class)->orderBy('start_seconds');
    }

    public function captions(): HasMany
    {
        return $this->hasMany(VlogCaption::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(DailyVlogStat::class);
    }

    public function slugHistory(): HasMany
    {
        return $this->hasMany(VlogSlugHistory::class);
    }

    public function strikes(): HasMany
    {
        return $this->hasMany(ContentStrike::class);
    }

    /* ---------------------------------------------------------------------
     | Scopes
     * ------------------------------------------------------------------ */

    /** Published and actually due (handles scheduled publishing). */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /** Everything a guest may browse in listings and feeds. */
    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query->published()->where('visibility', 'public');
    }

    /** Everything that may be opened directly by URL. */
    public function scopeOpenableByLink(Builder $query): Builder
    {
        return $query->published()->whereIn('visibility', ['public', 'unlisted']);
    }

    public function scopeForCreator(Builder $query, int $creatorId): Builder
    {
        return $query->where('creator_id', $creatorId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $driver = $this->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $escaped = addslashes($term);

            return $query->whereRaw(
                'MATCH(vlogs.title, vlogs.description, vlogs.location) AGAINST (? IN BOOLEAN MODE)',
                [static::booleanTerm($escaped)]
            );
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%");
        });
    }

    protected static function booleanTerm(string $term): string
    {
        $words = preg_split('/\s+/', trim($term)) ?: [];
        $words = array_filter($words, fn ($w) => mb_strlen($w) > 1);

        if (empty($words)) {
            return trim($term);
        }

        return implode(' ', array_map(fn ($w) => '+'.preg_replace('/[+\-><()~*"@]+/', '', $w).'*', $words));
    }

    /* ---------------------------------------------------------------------
     | Visibility
     * ------------------------------------------------------------------ */

    public function isLivePublished(): bool
    {
        return $this->status === 'published'
            && (is_null($this->published_at) || $this->published_at->lte(now()));
    }

    /** May this user open the watch page for this vlog? */
    public function isViewableBy(?User $user): bool
    {
        if ($user && ($user->id === $this->creator_id || $user->isAdmin())) {
            return true;
        }

        if (! $this->isLivePublished()) {
            return false;
        }

        return in_array($this->visibility, ['public', 'unlisted'], true);
    }

    /* ---------------------------------------------------------------------
     | URLs and media
     * ------------------------------------------------------------------ */

    protected function diskUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return media_url($path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->diskUrl($this->thumbnail) ?? asset('images/thumbnail-placeholder.svg');
    }

    public function getPreviewSpriteUrlAttribute(): ?string
    {
        return $this->diskUrl($this->preview_sprite);
    }

    /** Best available playable source. */
    public function getVideoSrcAttribute(): ?string
    {
        if (filled($this->hls_path)) {
            return $this->diskUrl($this->hls_path);
        }

        if (filled($this->video_file)) {
            return $this->diskUrl($this->video_file);
        }

        return $this->video_url ?: null;
    }

    public function getIsHlsAttribute(): bool
    {
        return filled($this->hls_path);
    }

    public function getIsExternalAttribute(): bool
    {
        return blank($this->video_file) && blank($this->hls_path) && filled($this->video_url);
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if (! $this->is_external) {
            return null;
        }

        $url = $this->video_url;

        if (preg_match('~youtube\.com/watch\?v=([\w-]+)~i', $url, $m)
            || preg_match('~youtu\.be/([\w-]+)~i', $url, $m)
            || preg_match('~youtube\.com/embed/([\w-]+)~i', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('~vimeo\.com/(\d+)~i', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }

    public function getUrlAttribute(): string
    {
        return route('vlogs.show', $this->slug);
    }

    public function getDurationLabelAttribute(): ?string
    {
        $seconds = $this->duration_seconds;

        if (! $seconds && filled($this->duration)) {
            return $this->duration;
        }

        if (! $seconds) {
            return null;
        }

        return $seconds >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60)
            : sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'published' && $this->scheduled_at && $this->scheduled_at->isFuture()) {
            return 'Scheduled';
        }

        return match ($this->status) {
            'draft' => 'Draft',
            'pending' => 'In review',
            'published' => 'Published',
            'rejected' => 'Rejected',
            'unpublished' => 'Unpublished',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'published' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            'unpublished' => 'muted',
            default => 'info',
        };
    }

    /* ---------------------------------------------------------------------
     | Slugs
     * ------------------------------------------------------------------ */

    public static function makeSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'vlog';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
            || VlogSlugHistory::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('vlog_id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Rename the slug but keep the old one working as a redirect. */
    public function renameSlug(string $title): void
    {
        $new = static::makeSlug($title, $this->id);

        if ($new === $this->slug) {
            return;
        }

        VlogSlugHistory::firstOrCreate([
            'vlog_id' => $this->id,
            'slug' => $this->slug,
        ]);

        $this->slug = $new;
    }

    /** Resolve a slug through history so shared links never break. */
    public static function resolveSlug(string $slug): ?self
    {
        $vlog = static::where('slug', $slug)->first();

        if ($vlog) {
            return $vlog;
        }

        $history = VlogSlugHistory::where('slug', $slug)->first();

        return $history?->vlog;
    }
}
