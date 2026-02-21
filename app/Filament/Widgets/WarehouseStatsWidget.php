<?php

namespace App\Filament\Widgets;

use App\Models\WarehouseEntry;
use App\Models\WarehouseEntryItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WarehouseStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalEntries    = WarehouseEntry::count();
        $pending         = WarehouseEntry::where('status', 'pending')->count();
        $completed       = WarehouseEntry::where('status', 'completed')->count();
        $cancelled       = WarehouseEntry::where('status', 'cancelled')->count();
        $totalItemsIn    = WarehouseEntryItem::whereHas('entry', fn ($q) => $q->where('status', 'completed'))->sum('quantity');
        $todayEntries    = WarehouseEntry::whereDate('received_at', today())->count();

        return [
            Stat::make('Total Entries', $totalEntries)
                ->description("{$todayEntries} received today")
                ->descriptionIcon('heroicon-m-archive-box-arrow-down')
                ->color('primary'),

            Stat::make('Pending', $pending)
                ->description('Awaiting completion')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Completed', $completed)
                ->description('Stock applied')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Cancelled', $cancelled)
                ->description('No stock change')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Total Units Received', number_format($totalItemsIn))
                ->description('From completed entries only')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),
        ];
    }
}