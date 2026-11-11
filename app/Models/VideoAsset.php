<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoAsset extends Model
{
    protected $fillable = [
        'vlog_id', 'type', 'label', 'width', 'height',
        'bitrate', 'path', 'disk', 'size', 'status',
    ];

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }
}
