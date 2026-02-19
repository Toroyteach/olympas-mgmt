<?php

namespace App\Filament\Widgets;

use App\Models\Shop\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class OrdersChart extends ChartWidget
{
    protected ?string $heading = 'Orders per Month (Current Year)';
    protected static ?int $sort = 1;
    protected ?string $maxHeight = '300px';

    public ?string $filter = null;

    protected int | string | array $columnSpan = 12;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $year = now()->year;

        return Cache::remember("orders_chart_{$year}", 600, function () use ($year) {
            $orders = Order::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->pluck('total', 'month');

            $data = collect(range(1, 12))->map(fn ($m) => $orders->get($m, 0))->toArray();
            $labels = collect(range(1, 12))->map(fn ($m) => Carbon::create()->month($m)->format('M'))->toArray();

            return [
                'datasets' => [[
                    'label' => 'Orders',
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245,158,11,0.1)',
                    'tension' => 0.4,
                ]],
                'labels' => $labels,
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
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]],
            ],
        ];
    }
}
