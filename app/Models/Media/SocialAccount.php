<?php

namespace App\Models\Media;

use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BaseRecyclableModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialAccount extends BaseRecyclableModel
{
    use SoftDeletes; 
    
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
