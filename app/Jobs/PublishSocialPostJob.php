<?php

namespace App\Jobs;

use App\Enums\DispatchStatus;
use App\Models\SocialPostDispatch;
use App\Services\SocialPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public SocialPostDispatch $dispatch
    ) {}

    public function handle(SocialPostingService $service): void
    {
        $this->dispatch->update(['status' => DispatchStatus::Queued]);
        $service->publishDispatch($this->dispatch);
    }

    public function failed(\Throwable $e): void
    {
        $this->dispatch->update([
            'status' => DispatchStatus::Failed,
            'error_message' => $e->getMessage(),
        ]);
    }
}
