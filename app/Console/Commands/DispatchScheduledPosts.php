<?php

namespace App\Console\Commands;

use App\Enums\DispatchStatus;
use App\Jobs\PublishSocialPostJob;
use App\Models\Media\SocialPost;
use Illuminate\Console\Command;

class DispatchScheduledPosts extends Command
{
    protected $signature = 'social:dispatch-scheduled';
    protected $description = 'Dispatch all social posts whose scheduled_at has passed';

    public function handle(): int
    {
        $posts = SocialPost::where('scheduled_at', '<=', now())
            ->whereHas('dispatches', fn ($q) => $q->where('status', DispatchStatus::Pending))
            ->with('dispatches')
            ->get();

        $count = 0;

        foreach ($posts as $post) {
            foreach ($post->dispatches as $dispatch) {
                if ($dispatch->status === DispatchStatus::Pending) {
                    PublishSocialPostJob::dispatch($dispatch);
                    $count++;
                }
            }
        }

        $this->info("Dispatched {$count} post(s) to queue.");

        return self::SUCCESS;
    }
}
