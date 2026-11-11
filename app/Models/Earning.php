<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Earning extends Model
{
    protected $fillable = [
        'creator_id', 'vlog_id', 'source', 'amount',
        'currency', 'period', 'status', 'notes',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }
}
