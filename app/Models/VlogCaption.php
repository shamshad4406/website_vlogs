<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VlogCaption extends Model
{
    protected $fillable = ['vlog_id', 'language', 'label', 'path', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? media_url($this->path) : null;
    }
}
