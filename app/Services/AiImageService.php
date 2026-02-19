<?php

namespace App\Services;

use App\Enums\ImageAspectRatio;
use App\Enums\ImageQuality;
use App\Models\AiImage;
use App\Traits\TracksAiUsage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Image as AiImageFile;
use Laravel\Ai\Image;

class AiImageService
{
    use TracksAiUsage;

    /**
     * Generate an image using the Laravel AI SDK.
     */
    public function generate(AiImage $aiImage): AiImage
    {
        $aiImage->markAsProcessing();
        $startTime = microtime(true);

        try {
            $builder = Image::of($this->buildPrompt($aiImage))
                ->quality($aiImage->quality->value)
                ->timeout((int) config('ai-images.timeout', 120));

            // Apply aspect ratio
            $builder = match ($aiImage->aspect_ratio) {
                ImageAspectRatio::Landscape => $builder->landscape(),
                ImageAspectRatio::Portrait => $builder->portrait(),
                default => $builder->square(),
            };

            // Attach reference image if provided
            if ($aiImage->reference_image_path) {
                $fullPath = Storage::path($aiImage->reference_image_path);

                $builder = $builder->attachments([
                    AiImageFile::fromPath($fullPath),
                ]);
            }

            // Generate the image
            $image = $builder->generate();

            // Store the generated image
            $filename = 'ai-images/' . Str::uuid() . '.png';
            $path = $image->storeAs($filename);

            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);

            $aiImage->markAsCompleted($path, $elapsedMs);

            $this->logUsage(
                userId: $aiImage->user_id,
                provider: $aiImage->provider,
                action: 'generate',
                status: 'success',
                aiImageId: $aiImage->id,
                responseTimeMs: $elapsedMs,
            );

            return $aiImage->fresh();
        } catch (\Throwable $e) {
            $elapsedMs = (int) ((microtime(true) - $startTime) * 1000);

            $aiImage->markAsFailed($e->getMessage());

            $this->logUsage(
                userId: $aiImage->user_id,
                provider: $aiImage->provider,
                action: 'generate',
                status: 'failed',
                aiImageId: $aiImage->id,
                responseTimeMs: $elapsedMs,
            );

            Log::error('AI Image generation failed', [
                'ai_image_id' => $aiImage->id,
                'error' => $e->getMessage(),
            ]);

            return $aiImage->fresh();
        }
    }

    /**
     * Build a professional prompt with context guardrails.
     */
    protected function buildPrompt(AiImage $aiImage): string
    {
        $systemContext = config('ai-images.system_prompt',
            'You are creating a professional image for a corporate communication. '
            . 'The output must be polished, brand-appropriate, and suitable for a general audience. '
            . 'Stay strictly within the context provided. Do not add unrelated elements.'
        );

        return $systemContext . "\n\n" . $aiImage->prompt;
    }
}
