<?php

namespace App\Filament\Widgets;

use App\Models\WarehouseEntry;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestWarehouseEntriesWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Latest Warehouse Entries';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WarehouseEntry::query()
                    ->with('user')
                    ->latest('received_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending'   => 'warning',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),

                TextColumn::make('user.name')
                    ->label('Received By'),

                TextColumn::make('received_at')
                    ->label('Received At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}