<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    protected $fillable = [
        'advertiser_id', 'name', 'budget', 'spent',
        'currency', 'starts_on', 'ends_on', 'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }

    public function advertisements(): HasMany
    {
        return $this->hasMany(Advertisement::class, 'campaign_id');
    }
}
