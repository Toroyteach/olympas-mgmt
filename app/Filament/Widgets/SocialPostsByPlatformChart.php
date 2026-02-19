<?php

namespace App\Filament\Widgets;

use App\Enums\DispatchStatus;
use App\Models\Media\SocialPostDispatch;
use Filament\Widgets\ChartWidget;

class SocialPostsByPlatformChart extends ChartWidget
{
    protected ?string $heading = 'Posts by Platform';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $data = SocialPostDispatch::query()
            ->join('social_accounts', 'social_post_dispatches.social_account_id', '=', 'social_accounts.id')
            ->selectRaw('social_accounts.platform, 
                SUM(CASE WHEN social_post_dispatches.status = ? THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN social_post_dispatches.status = ? THEN 1 ELSE 0 END) as failed', [
                DispatchStatus::Published->value,
                DispatchStatus::Failed->value,
            ])
            ->groupBy('social_accounts.platform')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Published',
                    'data' => $data->pluck('published')->toArray(),
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Failed',
                    'data' => $data->pluck('failed')->toArray(),
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => $data->pluck('platform')->map(fn($p) => ucfirst($p))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
