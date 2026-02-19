<?php

namespace App\Filament\Resources\Media\AiImages\Pages;

use App\Models\Media\AiImage;
use App\Models\Media\AiUsageLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AiImageStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        $todayCount = AiUsageLog::where('user_id', $userId)
            ->today()
            ->successful()
            ->count();

        $monthCount = AiUsageLog::where('user_id', $userId)
            ->thisMonth()
            ->successful()
            ->count();

        $dailyLimit = config('ai-images.daily_limit', 20);
        $monthlyLimit = config('ai-images.monthly_limit', 200);

        $avgTime = AiUsageLog::where('user_id', $userId)
            ->successful()
            ->thisMonth()
            ->avg('response_time_ms');

        $totalImages = AiImage::where('user_id', $userId)
            ->completed()
            ->count();

        $failedCount = AiUsageLog::where('user_id', $userId)
            ->thisMonth()
            ->where('status', 'failed')
            ->count();

        return [
            Stat::make('Today\'s Usage', $dailyLimit > 0
                ? "{$todayCount} / {$dailyLimit}"
                : $todayCount)
                ->description('Daily generations')
                ->color($dailyLimit > 0 && $todayCount >= $dailyLimit ? 'danger' : 'success')
                ->icon('heroicon-o-clock'),

            Stat::make('Monthly Usage', $monthlyLimit > 0
                ? "{$monthCount} / {$monthlyLimit}"
                : $monthCount)
                ->description('This month')
                ->color($monthlyLimit > 0 && $monthCount >= $monthlyLimit * 0.8 ? 'warning' : 'success')
                ->icon('heroicon-o-calendar'),

            Stat::make('Avg Generation Time', $avgTime
                ? number_format($avgTime / 1000, 1) . 's'
                : '—')
                ->description('This month')
                ->icon('heroicon-o-bolt'),

            Stat::make('Total Completed', $totalImages)
                ->description($failedCount > 0 ? "{$failedCount} failed this month" : 'All time')
                ->color($failedCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-photo'),
        ];
    }
}
