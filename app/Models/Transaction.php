<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'creator_id', 'vlog_id', 'type', 'amount', 'platform_fee',
        'currency', 'status', 'gateway', 'gateway_ref', 'message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }
}
