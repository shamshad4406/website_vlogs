<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function vlogs(): BelongsToMany
    {
        return $this->belongsToMany(Vlog::class, 'vlog_tags');
    }

    public function getUrlAttribute(): string
    {
        return route('tags.show', $this->slug);
    }

    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);

        return static::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        );
    }
}
