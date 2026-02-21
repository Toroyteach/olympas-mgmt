<?php

namespace App\Filament\Pages;

use App\Models\Shop\Product;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use BackedEnum;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ProductPricingInquiryPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Pricing Inquiry';
    protected static ?string $title = 'Product Pricing Inquiry';
    protected static string | UnitEnum | null $navigationGroup = 'Warehouse';
    protected static ?int $navigationSort = 2;
    protected  string $view = 'filament.app.pages.product-pricing-inquiry-page';

    // Quote cart state
    public array $cart = [];
    public ?array $quoteData = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->whereNull('deleted_at'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),

                TextColumn::make('price')
                    ->label('Selling Price')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('old_price')
                    ->label('Old Price')
                    ->money('KES')
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => new HtmlString("<s>{$state}</s>"))
                    ->placeholder('—'),

                TextColumn::make('cost')
                    ->label('Cost Price')
                    ->money('KES')
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('qty')
                    ->label('In Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state <= 0  => 'danger',
                        $state <= 10 => 'warning',
                        default      => 'success',
                    }),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('shop_brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name'),

                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock'  => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out'       => 'Out of Stock',
                    ])
                    ->query(function ($query, $state): void {
                        match ($state['value']) {
                            'in_stock'  => $query->where('qty', '>', 10),
                            'low_stock' => $query->whereBetween('qty', [1, 10]),
                            'out'       => $query->where('qty', '<=', 0),
                            default     => null,
                        };
                    }),
            ])
            ->actions([
                Action::make('add_to_quote')
                    ->label('Add to Quote')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function (Product $record): void {
                        $this->addToCart($record);
                    }),
            ])
            ->striped()
            ->defaultSort('name');
    }

    public function addToCart(Product $product): void
    {
        $key = 'product_' . $product->id;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
        } else {
            $this->cart[$key] = [
                'product_id'   => $product->id,
                'name'         => $product->name,
                'sku'          => $product->sku,
                'price'        => $product->price,
                'quantity'     => 1,
            ];
        }

        Notification::make()
            ->title("{$product->name} added to quote")
            ->success()
            ->send();
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($key);
            return;
        }

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity'] = $quantity;
        }
    }

    public function getCartTotal(): float
    {
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public function getCartCount(): int
    {
        return count($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];

        Notification::make()
            ->title('Quote cleared')
            ->success()
            ->send();
    }

    public function generateQuote(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('No items in quote')
                ->warning()
                ->send();
            return;
        }

        $this->quoteData = [
            'reference'  => 'QT-' . strtoupper(uniqid()),
            'generated'  => now()->format('d M Y H:i'),
            'items'      => $this->cart,
            'total'      => $this->getCartTotal(),
        ];

        Notification::make()
            ->title('Quote generated: ' . $this->quoteData['reference'])
            ->success()
            ->send();
    }
}
