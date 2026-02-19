<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $fillable = [
        'name',
        'platform',
        'is_active',
        'credentials',
        'notes',
    ];

    protected $casts = [
        'platform' => SocialPlatform::class,
        'is_active' => 'boolean',
        'credentials' => 'encrypted:array',
    ];

    public function dispatches(): HasMany
    {
        return $this->hasMany(SocialPostDispatch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
