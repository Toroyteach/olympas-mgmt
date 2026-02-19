<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Shop\Orders\OrderResource;
use App\Models\Shop\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Squire\Models\Currency;
use Filament\Tables;

class LatestOrders extends BaseWidget
{
    protected int | string | array $columnSpan = 12;
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->with('customer')->latest())
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->default('Guest'),

                TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->getStateUsing(fn(Order $record) => $record->status->getLabel())
                    ->color(fn(Order $record) => $record->status->getColor())
                    ->icon(fn(Order $record) => $record->status->getIcon()),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money(fn(Order $r) => strtolower($r->currency))
                    ->sortable(),

                TextColumn::make('shipping_method')
                    ->label('Shipping')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->url(fn(Order $r): string => OrderResource::getUrl('edit', ['record' => $r])),
            ]);
    }
}
