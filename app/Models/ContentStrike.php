<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentStrike extends Model
{
    protected $fillable = [
        'creator_id', 'vlog_id', 'issued_by', 'level', 'reason', 'notes',
        'status', 'appeal_text', 'appealed_at', 'resolved_at', 'expires_at',
    ];

    protected $casts = [
        'appealed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
