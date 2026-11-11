<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutAccount extends Model
{
    protected $fillable = [
        'creator_id', 'method', 'account_name', 'details', 'tax_id', 'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'details' => 'encrypted',
        'tax_id' => 'encrypted',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
