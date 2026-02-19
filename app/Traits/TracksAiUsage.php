<?php

namespace App\Traits;

use App\Models\AiUsageLog;

trait TracksAiUsage
{
    protected function logUsage(
        int $userId,
        string $provider,
        string $action,
        string $status,
        ?int $aiImageId = null,
        ?int $responseTimeMs = null,
    ): AiUsageLog {
        return AiUsageLog::create([
            'user_id' => $userId,
            'ai_image_id' => $aiImageId,
            'provider' => $provider,
            'action' => $action,
            'status' => $status,
            'response_time_ms' => $responseTimeMs,
        ]);
    }
}
