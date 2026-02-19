<?php

namespace App\Filament\Clusters\Products\Resources\Products\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('product-images')
                    ->conversion('thumb'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_visible')
                    ->label('Visibility')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('price')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('qty')
                    ->label('Quantity')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('security_stock')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('published_at')
                    ->label('Publishing date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                // ── Performance column ──────────────────────────────────────
                TextColumn::make('performance_score')
                    ->label('Performance')
                    ->getStateUsing(fn($record) => self::getPerformanceState($record))
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        str_starts_with($state, '🔥') => 'success',
                        str_starts_with($state, '📈') => 'info',
                        str_starts_with($state, '⚠️') => 'warning',
                        str_starts_with($state, '🔴') => 'danger',
                        default                        => 'gray',
                    })
                    ->tooltip(fn($record) => self::getPerformanceTooltip($record))
                    ->toggleable(),
                // ────────────────────────────────────────────────────────────
            ])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('name'),
                        TextConstraint::make('slug'),
                        TextConstraint::make('sku')
                            ->label('SKU (Stock Keeping Unit)'),
                        TextConstraint::make('barcode')
                            ->label('Barcode (ISBN, UPC, GTIN, etc.)'),
                        TextConstraint::make('description'),
                        NumberConstraint::make('old_price')
                            ->label('Compare at price')
                            ->icon('heroicon-m-currency-dollar'),
                        NumberConstraint::make('price')
                            ->icon('heroicon-m-currency-dollar'),
                        NumberConstraint::make('cost')
                            ->label('Cost per item')
                            ->icon('heroicon-m-currency-dollar'),
                        NumberConstraint::make('qty')
                            ->label('Quantity'),
                        NumberConstraint::make('security_stock'),
                        BooleanConstraint::make('is_visible')
                            ->label('Visibility'),
                        BooleanConstraint::make('featured'),
                        BooleanConstraint::make('backorder'),
                        BooleanConstraint::make('requires_shipping')
                            ->icon('heroicon-m-truck'),
                        DateConstraint::make('published_at')
                            ->label('Publishing date'),
                    ])
                    ->constraintPickerColumns(2),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters()
            ->recordActions([
                EditAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()
                    ->action(function (): void {
                        Notification::make()
                            ->title('Now, now, don\'t be cheeky, leave some records for others to play with!')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Performance helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns a cached metrics array for a product:
     *  units_sold      – total qty sold across non-cancelled orders (all time)
     *  revenue         – total revenue (unit_price × qty)
     *  orders_30d      – orders containing this product in last 30 days
     *  sell_through    – % of stock sold  (units_sold / (units_sold + current qty))
     *  margin_pct      – gross margin %   ((price - cost) / price * 100)
     *  low_stock       – bool: qty <= security_stock
     */
    private static function metrics($record): array
    {
        return Cache::remember("product_perf_{$record->id}", 300, function () use ($record) {
            $row = DB::table('shop_order_items as oi')
                ->join('shop_orders as o', 'o.id', '=', 'oi.shop_order_id')
                ->where('oi.shop_product_id', $record->id)
                ->whereNotIn('o.status', ['cancelled'])
                ->selectRaw('SUM(oi.qty) as units_sold, SUM(oi.qty * oi.unit_price) as revenue')
                ->first();

            $orders30d = DB::table('shop_order_items as oi')
                ->join('shop_orders as o', 'o.id', '=', 'oi.shop_order_id')
                ->where('oi.shop_product_id', $record->id)
                ->whereNotIn('o.status', ['cancelled'])
                ->where('o.created_at', '>=', now()->subDays(30))
                ->distinct('oi.shop_order_id')
                ->count('oi.shop_order_id');

            $unitsSold   = (int) ($row->units_sold ?? 0);
            $totalStock  = $unitsSold + max(0, (int) $record->qty);
            $sellThrough = $totalStock > 0 ? round(($unitsSold / $totalStock) * 100, 1) : 0;

            $price = (float) ($record->price ?? 0);
            $cost  = (float) ($record->cost  ?? 0);
            $marginPct = ($price > 0 && $cost > 0)
                ? round((($price - $cost) / $price) * 100, 1)
                : null;

            return [
                'units_sold'   => $unitsSold,
                'revenue'      => (float) ($row->revenue ?? 0),
                'orders_30d'   => $orders30d,
                'sell_through' => $sellThrough,
                'margin_pct'   => $marginPct,
                'low_stock'    => ($record->qty <= $record->security_stock && $record->security_stock > 0),
            ];
        });
    }

    /**
     * Maps metrics → a human-readable badge label with emoji tier:
     *   🔥 Top seller   – sell-through ≥ 70 % AND orders_30d ≥ 5
     *   📈 Growing      – sell-through ≥ 40 % OR  orders_30d ≥ 2
     *   ⚠️  Slow mover   – sell-through < 40 % AND units_sold > 0
     *   🔴 No sales     – zero units sold
     */
    private static function getPerformanceState($record): string
    {
        $m = self::metrics($record);

        if ($m['units_sold'] === 0) {
            return '🔴 No Sales';
        }

        if ($m['sell_through'] >= 70 && $m['orders_30d'] >= 5) {
            return '🔥 Top Seller';
        }

        if ($m['sell_through'] >= 40 || $m['orders_30d'] >= 2) {
            return '📈 Growing';
        }

        return '⚠️ Slow Mover';
    }

    /** Detailed breakdown shown on hover. */
    private static function getPerformanceTooltip($record): string
    {
        $m = self::metrics($record);

        $parts = [
            "Units sold (all time): {$m['units_sold']}",
            "Revenue: $" . number_format($m['revenue'], 2),
            "Orders (last 30d): {$m['orders_30d']}",
            "Sell-through: {$m['sell_through']}%",
        ];

        if ($m['margin_pct'] !== null) {
            $parts[] = "Gross margin: {$m['margin_pct']}%";
        }

        if ($m['low_stock']) {
            $parts[] = '⚠️ Below security stock!';
        }

        return implode(' | ', $parts);
    }
}
