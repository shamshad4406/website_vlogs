<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyChannelStat extends Model
{
    protected $fillable = [
        'creator_id', 'date', 'views', 'watch_seconds',
        'followers_gained', 'likes', 'comments', 'uploads',
    ];

    // date:Y-m-d so the value written matches the value queried on every
    // driver; a plain date cast writes a full datetime and breaks lookups.
    protected $casts = ['date' => 'date:Y-m-d'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
