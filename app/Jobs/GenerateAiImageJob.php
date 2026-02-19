<?php

namespace App\Jobs;

use App\Models\AiImage;
use App\Services\AiImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAiImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public AiImage $aiImage,
    ) {}

    public function handle(AiImageService $service): void
    {
        $service->generate($this->aiImage);
    }
}
