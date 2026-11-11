<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TakedownRequest extends Model
{
    protected $fillable = [
        'vlog_id', 'claimant_name', 'claimant_email', 'claimant_organisation',
        'work_description', 'infringement_details', 'good_faith_statement',
        'status', 'decision_notes', 'resolved_at', 'resolved_by',
    ];

    protected $casts = [
        'good_faith_statement' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function vlog(): BelongsTo
    {
        return $this->belongsTo(Vlog::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
