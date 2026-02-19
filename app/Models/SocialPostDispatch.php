<?php

namespace App\Models;

use App\Enums\DispatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostDispatch extends Model
{
    protected $fillable = [
        'social_post_id',
        'social_account_id',
        'status',
        'platform_post_id',
        'error_message',
        'attempts',
        'published_at',
    ];

    protected $casts = [
        'status' => DispatchStatus::class,
        'published_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function canRetry(): bool
    {
        return $this->status === DispatchStatus::Failed && $this->attempts < 3;
    }
}
