<?php

namespace App\Models\Media;

use App\Enums\ContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\BaseRecyclableModel;

class SocialPost extends Model
{
    protected $fillable = [
        'title',
        'content',
        'url',
        'content_type',
        'media_paths',
        'scheduled_at',
    ];

    protected $casts = [
        'content_type' => ContentType::class,
        'media_paths' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function dispatches(): HasMany
    {
        return $this->hasMany(SocialPostDispatch::class);
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null && $this->scheduled_at->isFuture();
    }

    public function getOverallStatusAttribute(): string
    {
        $statuses = $this->dispatches->pluck('status')->unique();

        if ($statuses->contains('failed')) {
            return 'has_failures';
        }
        if ($statuses->every(fn ($s) => $s->value === 'published')) {
            return 'published';
        }
        if ($statuses->contains('queued') || $statuses->contains('pending')) {
            return 'in_progress';
        }

        return 'draft';
    }
}
