<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('businessCustomersOnly')
                            ->boolean(),
                        DatePicker::make('startDate')
                            ->maxDate(fn(Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->minDate(fn(Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            // Tier 1: Key Stats (Full width top)
            \App\Filament\Widgets\StatsOverviewWidget::class,

            // Tier 2: Business Trends (Side-by-side charts)
            \App\Filament\Widgets\OrdersChart::class,
            \App\Filament\Widgets\CustomersChart::class,

            \App\Filament\Widgets\LatestOrders::class,
            // Tier 3: Operations (Tables usually look best taking more space)

            // Tier 4: Specialized Data
            \App\Filament\Widgets\AiImagesOverview::class,
            \App\Filament\Widgets\SocialMediaOverview::class,
            \App\Filament\Widgets\SocialPostsByPlatformChart::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 12;
    }
}
