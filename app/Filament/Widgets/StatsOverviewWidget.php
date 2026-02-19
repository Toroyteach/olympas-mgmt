<?php

namespace App\Filament\Widgets;

use App\Models\Shop\Customer;
use App\Models\Shop\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class StatsOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 12;

    protected function getStats(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : now()->startOfMonth();

        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : now()->endOfDay();

        $cacheKey = "stats_overview_{$startDate->timestamp}_{$endDate->timestamp}";

        [$revenue, $newCustomers, $newOrders, $sparkRevenue, $sparkOrders] = Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            $revenue = (float) Order::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotIn('status', ['cancelled'])
                ->sum('total_price');

            $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();

            $newOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();

            // 7-day sparkline going back from endDate
            $sparkRevenue = collect(range(6, 0))->map(
                fn($i) => (float) Order::whereDate('created_at', $endDate->copy()->subDays($i))
                    ->whereNotIn('status', ['cancelled'])
                    ->sum('total_price')
            )->toArray();

            $sparkOrders = collect(range(6, 0))->map(
                fn($i) => Order::whereDate('created_at', $endDate->copy()->subDays($i))->count()
            )->toArray();

            return [$revenue, $newCustomers, $newOrders, $sparkRevenue, $sparkOrders];
        });

        $fmt = function (float $n): string {
            if ($n < 1000) return Number::format($n, 2);
            if ($n < 1_000_000) return Number::format($n / 1000, 1) . 'k';
            return Number::format($n / 1_000_000, 2) . 'm';
        };

        return [
            Stat::make('Revenue', 'ksh' . $fmt($revenue))
                ->description('Excl. cancelled orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($sparkRevenue)
                ->color('success'),

            Stat::make('New Customers', Number::format($newCustomers))
                ->description('Registered in period')
                ->descriptionIcon('heroicon-m-user-plus')
                ->chart(array_fill(0, 7, $newCustomers / 7 ?: 0))
                ->color('info'),

            Stat::make('New Orders', Number::format($newOrders))
                ->description('All statuses')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart($sparkOrders)
                ->color('warning'),
        ];
    }
}
