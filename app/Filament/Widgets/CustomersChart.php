<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Shop\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CustomersChart extends ChartWidget
{
    protected ?string $heading = 'New Customers per Month (Current Year)';
    protected static ?int $sort = 2;
    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 12;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $year = now()->year;

        return Cache::remember("customers_chart_{$year}", 600, function () use ($year) {
            $customers = Customer::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->pluck('total', 'month');

            // Running cumulative total
            $running = 0;
            $cumulative = collect(range(1, 12))->map(function ($m) use ($customers, &$running) {
                $running += $customers->get($m, 0);
                return $running;
            })->toArray();

            $monthly = collect(range(1, 12))->map(fn ($m) => $customers->get($m, 0))->toArray();
            $labels  = collect(range(1, 12))->map(fn ($m) => Carbon::create()->month($m)->format('M'))->toArray();

            return [
                'datasets' => [
                    [
                        'label' => 'New this month',
                        'data' => $monthly,
                        'fill' => false,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16,185,129,0.1)',
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Cumulative',
                        'data' => $cumulative,
                        'fill' => 'start',
                        'borderColor' => '#6366f1',
                        'backgroundColor' => 'rgba(99,102,241,0.1)',
                        'tension' => 0.4,
                        'yAxisID' => 'y1',
                    ],
                ],
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
                'y'  => ['beginAtZero' => true, 'position' => 'left'],
                'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
            ],
        ];
    }
}
