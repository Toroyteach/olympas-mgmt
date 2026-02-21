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
                    ->color(fn(string $state): string => self::getPerformanceColor($state))
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
     *  orders_30d      – distinct orders containing this product in last 30 days
     *  orders_7d       – distinct orders in last 7 days (recency signal)
     *  sell_through    – % of stock sold (units_sold / (units_sold + current qty))
     *  margin_pct      – gross margin % ((price - cost) / price * 100)
     *  low_stock       – bool: qty <= security_stock
     *  is_dead_stock   – bool: no orders in 60+ days and qty > 0
     *  days_since_sale – days elapsed since the most recent order item
     *  score           – composite 0–100 performance percentage
     */
    private static function metrics($record): array
    {
        return Cache::remember("product_perf_{$record->id}", 300, function () use ($record) {

            // ── Aggregate all-time sales ─────────────────────────────────
            $row = DB::table('shop_order_items as oi')
                ->join('shop_orders as o', 'o.id', '=', 'oi.shop_order_id')
                ->where('oi.shop_product_id', $record->id)
                ->whereNotIn('o.status', ['cancelled'])
                ->selectRaw('
                    SUM(oi.qty)                  AS units_sold,
                    SUM(oi.qty * oi.unit_price)  AS revenue,
                    MAX(o.created_at)            AS last_order_at
                ')
                ->first();

            // ── Recent activity ──────────────────────────────────────────
            $orders30d = DB::table('shop_order_items as oi')
                ->join('shop_orders as o', 'o.id', '=', 'oi.shop_order_id')
                ->where('oi.shop_product_id', $record->id)
                ->whereNotIn('o.status', ['cancelled'])
                ->where('o.created_at', '>=', now()->subDays(30))
                ->distinct()
                ->count('oi.shop_order_id');

            $orders7d = DB::table('shop_order_items as oi')
                ->join('shop_orders as o', 'o.id', '=', 'oi.shop_order_id')
                ->where('oi.shop_product_id', $record->id)
                ->whereNotIn('o.status', ['cancelled'])
                ->where('o.created_at', '>=', now()->subDays(7))
                ->distinct()
                ->count('oi.shop_order_id');

            // ── Derived values ───────────────────────────────────────────
            $unitsSold   = (int) ($row->units_sold ?? 0);
            $currentQty  = max(0, (int) $record->qty);
            $totalStock  = $unitsSold + $currentQty;
            $sellThrough = $totalStock > 0
                ? round(($unitsSold / $totalStock) * 100, 1)
                : 0;

            $price     = (float) ($record->price ?? 0);
            $cost      = (float) ($record->cost  ?? 0);
            $marginPct = ($price > 0 && $cost > 0)
                ? round((($price - $cost) / $price) * 100, 1)
                : null;

            $lastOrderAt    = $row->last_order_at ? now()->parse($row->last_order_at) : null;
            $daysSinceSale  = $lastOrderAt ? (int) $lastOrderAt->diffInDays(now()) : null;
            $isDeadStock    = ($unitsSold === 0 || ($daysSinceSale !== null && $daysSinceSale >= 60))
                && $currentQty > 0;
            $lowStock       = ($record->security_stock > 0 && $currentQty <= $record->security_stock);

            // ── Composite score (0–100) ──────────────────────────────────
            $score = self::computeScore(
                sellThrough: $sellThrough,
                orders30d: $orders30d,
                orders7d: $orders7d,
                marginPct: $marginPct,
                isDeadStock: $isDeadStock,
                lowStock: $lowStock,
                daysSinceSale: $daysSinceSale,
                unitsSold: $unitsSold,
            );

            return [
                'units_sold'     => $unitsSold,
                'revenue'        => (float) ($row->revenue ?? 0),
                'orders_30d'     => $orders30d,
                'orders_7d'      => $orders7d,
                'sell_through'   => $sellThrough,
                'margin_pct'     => $marginPct,
                'low_stock'      => $lowStock,
                'is_dead_stock'  => $isDeadStock,
                'days_since_sale' => $daysSinceSale,
                'score'          => $score,
            ];
        });
    }

    /**
     * Weighted composite score — 0 to 100.
     *
     * Component weights:
     *   Sell-through rate   40 pts  – how much of total stock has been sold
     *   Recent demand       30 pts  – orders in last 30 days (capped at 10 orders = full score)
     *   Margin health       20 pts  – gross margin % (capped at 60 % = full score)
     *   Stock health        10 pts  – penalises dead stock and low stock
     *
     * Dead-stock hard penalty: score is capped at 20 when product is dead stock.
     */
    private static function computeScore(
        float   $sellThrough,
        int     $orders30d,
        int     $orders7d,
        ?float  $marginPct,
        bool    $isDeadStock,
        bool    $lowStock,
        ?int    $daysSinceSale,
        int     $unitsSold,
    ): int {
        // 1. Sell-through (0–40 pts)
        $sellThroughScore = min(40, ($sellThrough / 100) * 40);

        // 2. Recent demand (0–30 pts)
        //    10+ orders in 30d = full 30 pts; scaled linearly below that.
        //    Bonus 5 pts if there were orders in the last 7 days (already within the 30d cap).
        $demandScore = min(30, ($orders30d / 10) * 30);
        if ($orders7d > 0) {
            $demandScore = min(30, $demandScore + 5);
        }

        // 3. Margin health (0–20 pts)
        //    60 %+ margin = full 20 pts; null margin = 10 pts (neutral).
        $marginScore = match (true) {
            $marginPct === null      => 10,
            $marginPct >= 60         => 20,
            $marginPct >= 0          => ($marginPct / 60) * 20,
            default                  => 0,   // negative margin
        };

        // 4. Stock health (0–10 pts)
        $stockScore = 10;
        if ($lowStock)     $stockScore -= 5;
        if ($isDeadStock)  $stockScore -= 10; // can go negative; clamped below

        // ── Recency decay ─────────────────────────────────────────────────
        // If the last sale was 30–59 days ago, apply a partial decay on demand.
        if ($daysSinceSale !== null && $daysSinceSale >= 30 && ! $isDeadStock) {
            $decayFactor = 1 - (($daysSinceSale - 30) / 30) * 0.5; // up to –50% of demand score
            $demandScore = $demandScore * max(0.5, $decayFactor);
        }

        $raw = $sellThroughScore + $demandScore + $marginScore + $stockScore;
        $clamped = (int) round(max(0, min(100, $raw)));

        // Hard cap: dead stock cannot score above 20
        if ($isDeadStock) {
            $clamped = min(20, $clamped);
        }

        return $clamped;
    }

    /**
     * Badge label: "{score}% · {tier label}"
     */
    private static function getPerformanceState($record): string
    {
        $m = self::metrics($record);
        $score = $m['score'];

        $label = match (true) {
            $score >= 80 => 'Excellent',
            $score >= 60 => 'Good',
            $score >= 40 => 'Average',
            $score >= 20 => 'Poor',
            default      => 'Dead Stock',
        };

        return "{$score}% · {$label}";
    }

    /**
     * Gradient color: green → yellow → orange → red as score falls.
     *
     * Filament badge colors: success (green) | info (blue) | warning (yellow/orange) | danger (red) | gray
     */
    private static function getPerformanceColor(string $state): string
    {
        // Extract the numeric score from the badge label "75% · Good"
        preg_match('/^(\d+)%/', $state, $matches);
        $score = (int) ($matches[1] ?? 0);

        return match (true) {
            $score >= 80 => 'success',  // green
            $score >= 60 => 'info',     // blue-green (good)
            $score >= 40 => 'warning',  // amber
            $score >= 20 => 'orange',   // Filament custom / falls back gracefully
            default      => 'danger',   // red
        };
    }

    /**
     * Detailed breakdown shown on hover.
     */
    private static function getPerformanceTooltip($record): string
    {
        $m = self::metrics($record);

        $parts = [
            "Score: {$m['score']}%",
            "Units sold (all time): {$m['units_sold']}",
            'Revenue: $' . number_format($m['revenue'], 2),
            "Orders (30d): {$m['orders_30d']}",
            "Orders (7d): {$m['orders_7d']}",
            "Sell-through: {$m['sell_through']}%",
        ];

        if ($m['margin_pct'] !== null) {
            $parts[] = "Gross margin: {$m['margin_pct']}%";
        }

        if ($m['days_since_sale'] !== null) {
            $parts[] = "Last sale: {$m['days_since_sale']}d ago";
        }

        if ($m['is_dead_stock']) {
            $parts[] = '⚠️ Dead stock!';
        } elseif ($m['low_stock']) {
            $parts[] = '⚠️ Below security stock!';
        }

        return implode(' | ', $parts);
    }
}
