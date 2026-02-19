<?php

namespace App\Filament\Resources\Media\SocialPosts\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Enums\DispatchStatus;
use App\Filament\Resources\Media\SocialPosts\SocialPostResource;
use App\Jobs\PublishSocialPostJob;
use App\Models\Media\SocialPostDispatch;

class CreateSocialPost extends CreateRecord
{
    protected static string $resource = SocialPostResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove account_ids from main data — handled in afterCreate
        unset($data['account_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $accountIds = $this->data['account_ids'] ?? [];

        foreach ($accountIds as $accountId) {
            $dispatch = SocialPostDispatch::create([
                'social_post_id' => $this->record->id,
                'social_account_id' => $accountId,
                'status' => DispatchStatus::Pending,
            ]);

            // If not scheduled, dispatch immediately
            if (! $this->record->isScheduled()) {
                PublishSocialPostJob::dispatch($dispatch);
            }
        }
    }
}
