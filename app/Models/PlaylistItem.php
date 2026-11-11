<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaylistItem extends Model
{
    protected $fillable = ['playlist_id', 'vlog_id', 'position'];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }
}
