<?php

namespace App\Filament\Resources\WarehouseEntries\Pages;

use App\Filament\Resources\WarehouseEntries\WarehouseEntryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseEntries extends ListRecords
{
    protected static string $resource = WarehouseEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receive_products')
                ->label('Receive Products')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('primary')
                ->url(fn(): string => \App\Filament\Pages\ReceiveProductsPage::getUrl()),
        ];
    }
}
