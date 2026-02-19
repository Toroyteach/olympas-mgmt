<?php

namespace App\Models;

use App\Enums\ImageAspectRatio;
use App\Enums\ImageQuality;
use App\Enums\ImageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class AiImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'prompt',
        'provider',
        'aspect_ratio',
        'quality',
        'reference_image_path',
        'generated_image_path',
        'status',
        'error_message',
        'generation_time_ms',
        'metadata',
    ];

    protected $casts = [
        'status' => ImageStatus::class,
        'aspect_ratio' => ImageAspectRatio::class,
        'quality' => ImageQuality::class,
        'metadata' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    // ── Accessors ──────────────────────────────────────────

    public function getGeneratedImageUrlAttribute(): ?string
    {
        return $this->generated_image_path
            ? Storage::url($this->generated_image_path)
            : null;
    }

    public function getReferenceImageUrlAttribute(): ?string
    {
        return $this->reference_image_path
            ? Storage::url($this->reference_image_path)
            : null;
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', ImageStatus::Completed);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ────────────────────────────────────────────

    public function markAsProcessing(): void
    {
        $this->update(['status' => ImageStatus::Processing]);
    }

    public function markAsCompleted(string $path, int $timeMs, ?array $meta = null): void
    {
        $this->update([
            'status' => ImageStatus::Completed,
            'generated_image_path' => $path,
            'generation_time_ms' => $timeMs,
            'metadata' => $meta,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => ImageStatus::Failed,
            'error_message' => $error,
        ]);
    }
}
