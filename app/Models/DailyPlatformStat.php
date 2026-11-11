<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPlatformStat extends Model
{
    protected $fillable = [
        'date', 'active_users', 'new_users', 'new_creators',
        'uploads', 'views', 'watch_seconds', 'reports',
    ];

    // date:Y-m-d so the value written matches the value queried on every
    // driver; a plain date cast writes a full datetime and breaks lookups.
    protected $casts = ['date' => 'date:Y-m-d'];
}
