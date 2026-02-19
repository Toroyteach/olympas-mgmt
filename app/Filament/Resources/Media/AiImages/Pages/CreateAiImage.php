<?php

namespace App\Filament\Resources\Media\AiImages\Pages;

use App\Filament\Resources\Media\AiImages\AiImageResource;
use App\Jobs\GenerateAiImageJob;
use App\Models\Media\AiUsageLog;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAiImage extends CreateRecord
{
    protected static string $resource = AiImageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        return $data;
    }

    protected function beforeCreate(): void
    {
        $userId = auth()->id();

        // Check daily limit
        $dailyLimit = config('ai-images.daily_limit', 20);
        if ($dailyLimit > 0) {
            $todayCount = AiUsageLog::where('user_id', $userId)->today()->successful()->count();

            if ($todayCount >= $dailyLimit) {
                Notification::make()
                    ->title('Daily limit reached')
                    ->body("You have used all {$dailyLimit} daily generations. Try again tomorrow.")
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        // Check monthly limit
        $monthlyLimit = config('ai-images.monthly_limit', 200);
        if ($monthlyLimit > 0) {
            $monthCount = AiUsageLog::where('user_id', $userId)->thisMonth()->successful()->count();

            if ($monthCount >= $monthlyLimit) {
                Notification::make()
                    ->title('Monthly limit reached')
                    ->body("You have used all {$monthlyLimit} monthly generations.")
                    ->danger()
                    ->send();

                $this->halt();
            }
        }
    }

    protected function afterCreate(): void
    {
        // Dispatch async generation job
        GenerateAiImageJob::dispatch($this->record);

        Notification::make()
            ->title('Image generation started')
            ->body('Your image is being generated. This may take up to 2 minutes.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
