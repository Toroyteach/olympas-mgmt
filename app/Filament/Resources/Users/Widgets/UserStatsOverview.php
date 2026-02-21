<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $total       = User::count();
        $active      = User::where('status', 'active')->count();
        $pending     = User::whereNotNull('invitation_token')->whereNull('invitation_accepted_at')->count();
        $newThisMonth = User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        return [
            Stat::make('Total Users', $total)
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Active Users', $active)
                ->description(number_format($total > 0 ? ($active / $total) * 100 : 0, 1) . '% of total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending Invitations', $pending)
                ->description('Awaiting account setup')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('warning'),

            Stat::make('New This Month', $newThisMonth)
                ->description('Users joined this month')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),
        ];
    }
}
