<?php

namespace App\Filament\Resources\WarehouseEntries\Schemas;

use App\Models\Shop\Brand;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WarehouseEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([

                    Step::make('Entry Details')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->description('Supplier and entry information')
                        ->columns(2)
                        ->schema([
                            TextInput::make('supplier_name')
                                ->label('Supplier Name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('supplier_invoice')
                                ->label('Supplier Invoice #')
                                ->maxLength(255),

                            Select::make('status')
                                ->label('Entry Status')
                                ->options([
                                    'pending'   => 'Pending',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->default('pending')
                                ->required()
                                ->live(),

                            DateTimePicker::make('received_at')
                                ->label('Received At')
                                ->required()
                                ->default(now()),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ]),

                    Step::make('Products')
                        ->icon('heroicon-o-cube')
                        ->description('Add existing or register new products')
                        ->schema([
                            Repeater::make('items')
                                ->label('Products')
                                ->addActionLabel('Add Product')
                                ->reorderable(false)
                                ->collapsible()
                                ->itemLabel(
                                    fn(array $state): string => ($state['product_mode'] ?? 'existing') === 'new'
                                        ? ('New: ' . ($state['new_product_name'] ?? 'Unnamed'))
                                        : ('Existing Product')
                                )
                                ->schema([
                                    Radio::make('product_mode')
                                        ->label('Product Entry Type')
                                        ->options([
                                            'existing' => 'Update Existing Product',
                                            'new'      => 'Register New Product',
                                        ])
                                        ->default('existing')
                                        ->inline()
                                        ->live()
                                        ->columnSpanFull(),

                                    // Existing product
                                    Select::make('shop_product_id')
                                        ->label('Search Product')
                                        ->options(
                                            fn() => Product::query()
                                                ->orderBy('name')
                                                ->get()
                                                ->mapWithKeys(fn($p) => [
                                                    $p->id => $p->name . ($p->sku ? " [{$p->sku}]" : ''),
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->required()
                                        ->visible(
                                            fn(\Filament\Schemas\Components\Utilities\Get $get) => ($get('product_mode') ?? 'existing') === 'existing'
                                        )
                                        ->columnSpanFull(),

                                    TextInput::make('quantity')
                                        ->label('Quantity Received')
                                        ->numeric()
                                        ->minValue(1)
                                        ->required()
                                        ->visible(
                                            fn(\Filament\Schemas\Components\Utilities\Get $get) => ($get('product_mode') ?? 'existing') === 'existing'
                                        ),

                                    TextInput::make('unit_cost')
                                        ->label('Unit Cost (KES)')
                                        ->numeric()
                                        ->prefix('KES')
                                        ->minValue(0)
                                        ->visible(
                                            fn(\Filament\Schemas\Components\Utilities\Get $get) => ($get('product_mode') ?? 'existing') === 'existing'
                                        ),

                                    Textarea::make('item_notes')
                                        ->label('Item Notes')
                                        ->rows(2)
                                        ->visible(
                                            fn(\Filament\Schemas\Components\Utilities\Get $get) => ($get('product_mode') ?? 'existing') === 'existing'
                                        )
                                        ->columnSpanFull(),

                                    // New product
                                    Section::make('New Product Details')
                                        ->visible(
                                            fn(\Filament\Schemas\Components\Utilities\Get $get) => ($get('product_mode') ?? 'existing') === 'new'
                                        )
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('new_product_name')
                                                ->label('Product Name')
                                                ->required()
                                                ->maxLength(255)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(
                                                    fn($state, \Filament\Schemas\Components\Utilities\Set $set) =>
                                                    $set('new_product_slug', Str::slug($state ?? ''))
                                                ),

                                            TextInput::make('new_product_slug')
                                                ->label('Slug')
                                                ->disabled()
                                                ->dehydrated()
                                                ->maxLength(255),

                                            TextInput::make('new_product_sku')
                                                ->label('SKU')
                                                ->maxLength(255),

                                            TextInput::make('new_product_barcode')
                                                ->label('Barcode')
                                                ->maxLength(255),

                                            TextInput::make('new_product_price')
                                                ->label('Selling Price (KES)')
                                                ->numeric()
                                                ->required()
                                                ->prefix('KES'),

                                            TextInput::make('new_product_cost')
                                                ->label('Cost per Item (KES)')
                                                ->numeric()
                                                ->prefix('KES'),

                                            TextInput::make('new_quantity')
                                                ->label('Quantity Received')
                                                ->numeric()
                                                ->minValue(1)
                                                ->required(),

                                            TextInput::make('new_unit_cost')
                                                ->label('Supplier Unit Cost (KES)')
                                                ->numeric()
                                                ->prefix('KES')
                                                ->minValue(0),

                                            Select::make('new_product_brand_id')
                                                ->label('Brand')
                                                ->options(
                                                    fn() => Brand::query()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray()
                                                )
                                                ->searchable(),

                                            Select::make('new_product_categories')
                                                ->label('Categories')
                                                ->options(
                                                    fn() => Category::query()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray()
                                                )
                                                ->multiple()
                                                ->searchable(),

                                            RichEditor::make('new_product_description')
                                                ->label('Description')
                                                ->columnSpanFull(),

                                            FileUpload::make('new_product_images')
                                                ->label('Product Images')
                                                ->image()
                                                ->multiple()
                                                ->maxFiles(5)
                                                ->reorderable()
                                                ->directory('products/temp')
                                                ->columnSpanFull(),

                                            Toggle::make('new_product_is_visible')
                                                ->label('Visible / Published')
                                                ->default(true),

                                            Checkbox::make('new_product_requires_shipping')
                                                ->label('Requires Shipping'),

                                            Checkbox::make('new_product_backorder')
                                                ->label('Allow Backorder'),

                                            DatePicker::make('new_product_published_at')
                                                ->label('Publishing Date')
                                                ->default(now()),
                                        ]),
                                ])
                                ->columns(2)
                                ->minItems(1),
                        ]),

                    Step::make('Review')
                        ->icon('heroicon-o-check-circle')
                        ->description('Review and confirm')
                        ->columns(2)
                        ->schema([
                            TextInput::make('supplier_name')
                                ->label('Supplier')
                                ->disabled(),

                            TextInput::make('status')
                                ->label('Status')
                                ->disabled(),

                            DateTimePicker::make('received_at')
                                ->label('Received At')
                                ->disabled(),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->disabled()
                                ->columnSpanFull(),
                        ]),

                ]),
            ]);
    }
}
