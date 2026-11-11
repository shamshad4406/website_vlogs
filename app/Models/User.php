<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'username',
        'avatar',
        'bio',
        'is_active',
        'last_login_at',
        'locale',
        'theme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'banned_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relations
     * ------------------------------------------------------------------ */

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function creatorProfile(): HasOne
    {
        return $this->hasOne(CreatorProfile::class);
    }

    public function creatorApplication(): HasOne
    {
        return $this->hasOne(CreatorApplication::class);
    }

    public function vlogs(): HasMany
    {
        return $this->hasMany(Vlog::class, 'creator_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function following(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'creator_id');
    }

    public function followedCreators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'creator_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->where('is_read', false)->latest();
    }

    public function vlogReports(): HasMany
    {
        return $this->hasMany(VlogReport::class);
    }

    public function commentReports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class, 'owner_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(UserSetting::class);
    }

    public function strikes(): HasMany
    {
        return $this->hasMany(ContentStrike::class, 'creator_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class, 'creator_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'creator_id');
    }

    public function payoutAccount(): HasOne
    {
        return $this->hasOne(PayoutAccount::class, 'creator_id');
    }

    public function channelStats(): HasMany
    {
        return $this->hasMany(DailyChannelStat::class, 'creator_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class, 'admin_id');
    }

    /* ---------------------------------------------------------------------
     | Roles and permissions
     * ------------------------------------------------------------------ */

    public function isAdmin(): bool
    {
        return $this->role && $this->role->slug === 'admin';
    }

    public function isCreator(): bool
    {
        return $this->role && $this->role->slug === 'creator';
    }

    public function isUser(): bool
    {
        return $this->role && $this->role->slug === 'user';
    }

    public function isModerator(): bool
    {
        return $this->role && in_array($this->role->slug, ['admin', 'moderator'], true);
    }

    /** Fine-grained permission check backed by the role_permissions table. */
    public function hasPermission(string $slug): bool
    {
        if (! $this->role_id) {
            return false;
        }

        if ($this->role && $this->role->slug === 'admin') {
            return true;
        }

        $permissions = Cache::remember(
            "role.{$this->role_id}.permissions",
            600,
            fn () => $this->role?->permissions()->pluck('slug')->all() ?? []
        );

        return in_array($slug, $permissions, true);
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /** True when the account may sign in: not banned and not deactivated. */
    public function canSignIn(): bool
    {
        return ! $this->isBanned() && (bool) $this->is_active;
    }

    /**
     * Where this account lands after signing in, verifying an email or
     * confirming a password. Staff and creators get their own workspace
     * instead of the viewer library.
     */
    public function homeRoute(): string
    {
        if ($this->isModerator()) {
            return route('admin.dashboard', absolute: false);
        }

        if ($this->isCreator()) {
            return route('creator.dashboard', absolute: false);
        }

        return route('dashboard', absolute: false);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /* ---------------------------------------------------------------------
     | Helpers
     * ------------------------------------------------------------------ */

    public function getAvatarUrlAttribute(): string
    {
        $avatar = $this->creatorProfile?->profile_image ?: $this->avatar;

        if (filled($avatar)) {
            if (Str::startsWith($avatar, ['http://', 'https://'])) {
                return $avatar;
            }

            return media_url($avatar);
        }

        return route('avatar.initials', ['name' => urlencode($this->initials())]);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = collect($parts)->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return $initials !== '' ? $initials : 'U';
    }

    public function getChannelNameAttribute(): string
    {
        return $this->creatorProfile?->channel_name ?: $this->name;
    }

    public function getProfileUrlAttribute(): ?string
    {
        return $this->username ? route('creators.show', $this->username) : null;
    }

    public function setting(string $key, $default = null)
    {
        $all = Cache::remember(
            "user.{$this->id}.settings",
            300,
            fn () => $this->settings()->pluck('value', 'key')->all()
        );

        return $all[$key] ?? $default;
    }

    public function putSetting(string $key, $value): void
    {
        $this->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("user.{$this->id}.settings");
    }

    public function wantsEmail(string $type): bool
    {
        return (string) $this->setting('email_'.$type, '1') === '1';
    }
}
