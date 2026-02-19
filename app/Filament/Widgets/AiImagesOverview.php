<?php

namespace App\Filament\Widgets;

use App\Models\Media\AiImage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AiImagesOverview extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 12;

    protected function getStats(): array
    {
        [$completed, $failed, $pending, $avgMs] = Cache::remember('ai_images_overview', 300, function () {
            return [
                AiImage::where('status', 'completed')->count(),
                AiImage::where('status', 'failed')->count(),
                AiImage::whereIn('status', ['pending', 'processing'])->count(),
                (int) AiImage::where('status', 'completed')->whereNotNull('generation_time_ms')->avg('generation_time_ms'),
            ];
        });

        return [
            Stat::make('Generated Images', $completed)
                ->description('Successfully completed')
                ->icon('heroicon-o-photo')
                ->color('success'),

            Stat::make('Failed Generations', $failed)
                ->description("{$pending} pending/processing")
                ->icon('heroicon-o-exclamation-circle')
                ->color($failed > 0 ? 'danger' : 'success'),

            Stat::make('Avg Generation Time', $avgMs ? round($avgMs / 1000, 1) . 's' : 'N/A')
                ->description('Per completed image')
                ->icon('heroicon-o-clock')
                ->color('info'),
        ];
    }
}