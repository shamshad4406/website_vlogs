<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VlogChapter extends Model
{
    protected $fillable = ['vlog_id', 'start_seconds', 'title'];

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }

    public function getTimecodeAttribute(): string
    {
        $s = (int) $this->start_seconds;

        return $s >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60)
            : sprintf('%d:%02d', intdiv($s, 60), $s % 60);
    }
}
