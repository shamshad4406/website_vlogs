<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchHistory extends Model
{
    use HasFactory;

    protected $table = 'watch_history';

    protected $fillable = [
        'user_id', 'vlog_id', 'watch_duration',
        'position_seconds', 'completed', 'watched_at',
    ];

    protected $casts = [
        'watched_at' => 'datetime',
        'completed' => 'boolean',
        'watch_duration' => 'integer',
        'position_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->vlog?->duration_seconds;

        if (! $total) {
            return 0;
        }

        return (int) min(100, round($this->position_seconds / $total * 100));
    }
}
