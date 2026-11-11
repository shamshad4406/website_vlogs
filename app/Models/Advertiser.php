<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Advertiser extends Model
{
    protected $fillable = ['name', 'contact_email', 'contact_phone', 'website', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }
}
