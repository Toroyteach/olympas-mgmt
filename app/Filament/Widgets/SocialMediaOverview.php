<?php

namespace App\Filament\Widgets;

use App\Enums\DispatchStatus;

use App\Models\Media\SocialPostDispatch;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\ChartWidget;

class SocialMediaOverview extends ChartWidget
{
    protected ?string $heading = 'Dispatches by Platform';
    protected static ?int $sort = 5;
    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return Cache::remember('social_platform_chart', 300, function () {
            $rows = SocialPostDispatch::query()
                ->join('social_accounts', 'social_post_dispatches.social_account_id', '=', 'social_accounts.id')
                ->selectRaw(
                    "social_accounts.platform,
                    SUM(CASE WHEN social_post_dispatches.status = ? THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN social_post_dispatches.status = ? THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN social_post_dispatches.status IN (?,?) THEN 1 ELSE 0 END) as pending",
                    [
                        DispatchStatus::Published->value,
                        DispatchStatus::Failed->value,
                        DispatchStatus::Pending->value,
                        DispatchStatus::Queued->value,
                    ]
                )
                ->groupBy('social_accounts.platform')
                ->get();

            return [
                'datasets' => [
                    ['label' => 'Published', 'data' => $rows->pluck('published')->toArray(), 'backgroundColor' => '#10b981'],
                    ['label' => 'Pending',   'data' => $rows->pluck('pending')->toArray(),   'backgroundColor' => '#f59e0b'],
                    ['label' => 'Failed',    'data' => $rows->pluck('failed')->toArray(),    'backgroundColor' => '#ef4444'],
                ],
                'labels' => $rows->pluck('platform')->map(fn($p) => ucfirst($p))->toArray(),
            ];
        });
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => true]],
            'scales' => [
                'x' => ['stacked' => false],
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
