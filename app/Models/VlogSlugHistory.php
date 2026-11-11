<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VlogSlugHistory extends Model
{
    protected $table = 'vlog_slug_history';

    protected $fillable = ['vlog_id', 'slug'];

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }
}
