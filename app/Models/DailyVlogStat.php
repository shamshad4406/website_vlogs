<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyVlogStat extends Model
{
    protected $fillable = [
        'vlog_id', 'date', 'views', 'unique_viewers',
        'watch_seconds', 'likes', 'comments',
    ];

    // date:Y-m-d so the value written matches the value queried on every
    // driver; a plain date cast writes a full datetime and breaks lookups.
    protected $casts = ['date' => 'date:Y-m-d'];

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }
}
