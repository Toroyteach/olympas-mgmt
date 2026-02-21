<?php

namespace App\Filament\Pages;

use App\Models\WarehouseEntry;
use App\Models\WarehouseEntryItem;
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
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use UnitEnum;

class ReceiveProductsPage extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationLabel = 'Receive Products';
    protected static ?string $title = 'Receive Products to Warehouse';
    protected static string|UnitEnum|null $navigationGroup = 'Warehouse';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.app.pages.receive-products-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'received_at' => now(),
            'status'      => 'pending',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([

                    // ─── Step 1: Entry Details ────────────────────────────────────
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
                                ->required(),

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

                    // ─── Step 2: Products ─────────────────────────────────────────
                    Step::make('Products')
                        ->icon('heroicon-o-cube')
                        ->description('Add existing products or register new ones')
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

                                    // ── Existing product fields ───────────────────
                                    Select::make('shop_product_id')
                                        ->label('Search Product')
                                        ->options(
                                            fn() => \App\Models\Shop\Product::query()
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

                                    // ── New product fields ────────────────────────
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
                                                    fn() => \App\Models\Shop\Brand::query()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray()
                                                )
                                                ->searchable(),

                                            Select::make('new_product_categories')
                                                ->label('Categories')
                                                ->options(
                                                    fn() => \App\Models\Shop\Category::query()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id')
                                                        ->toArray()
                                                )
                                                ->multiple()
                                                ->searchable(),

                                            RichEditor::make('new_product_description')
                                                ->label('Description')
                                                ->columnSpanFull(),

                                            Toggle::make('new_product_is_visible')
                                                ->label('Visible / Published')
                                                ->default(true),

                                            Checkbox::make('new_product_requires_shipping')
                                                ->label('Requires Shipping'),

                                            Checkbox::make('new_product_backorder')
                                                ->label('Allow Backorder'),
                                        ]),
                                ])
                                ->columns(1)
                                ->minItems(1),
                        ]),

                    // ─── Step 3: Review ───────────────────────────────────────────
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

                ])->submitAction(
                    Action::make('submit')
                        ->label('Save Entry')
                        ->submit('save')
                ),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data): void {
            $status = $data['status'];

            $entry = \App\Models\WarehouseEntry::create([
                'user_id'          => Auth::id(),
                'reference_number' => 'WE-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'supplier_name'    => $data['supplier_name'],
                'supplier_invoice' => $data['supplier_invoice'] ?? null,
                'status'           => $status,
                'notes'            => $data['notes'] ?? null,
                'received_at'      => $data['received_at'],
            ]);

            foreach ($data['items'] as $item) {
                $mode = $item['product_mode'] ?? 'existing';

                if ($mode === 'new') {
                    $product = \App\Models\Shop\Product::create([
                        'name'              => $item['new_product_name'],
                        'slug'              => $item['new_product_slug'] ?? \Illuminate\Support\Str::slug($item['new_product_name']),
                        'sku'               => $item['new_product_sku'] ?? null,
                        'barcode'           => $item['new_product_barcode'] ?? null,
                        'description'       => $item['new_product_description'] ?? null,
                        'price'             => $item['new_product_price'],
                        'cost'              => $item['new_product_cost'] ?? null,
                        'qty'               => 0,
                        'is_visible'        => $item['new_product_is_visible'] ?? true,
                        'requires_shipping' => $item['new_product_requires_shipping'] ?? false,
                        'backorder'         => $item['new_product_backorder'] ?? false,
                        'published_at'      => $item['new_product_published_at'] ?? now(),
                        'shop_brand_id'     => $item['new_product_brand_id'] ?? null,
                    ]);

                    if (! empty($item['new_product_categories'])) {
                        $product->categories()->sync($item['new_product_categories']);
                    }

                    $qty = (int) ($item['new_quantity'] ?? 0);

                    \App\Models\WarehouseEntryItem::create([
                        'warehouse_entry_id'   => $entry->id,
                        'shop_product_id'      => $product->id,
                        'quantity'             => $qty,
                        'unit_cost'            => $item['new_unit_cost'] ?? null,
                        'notes'                => null,
                        'product_created_here' => true,
                    ]);

                    if ($status === 'completed') {
                        // Active — apply stock
                        if ($qty > 0) {
                            $product->increment('qty', $qty);
                        }
                    } else {
                        // Pending/cancelled — soft-delete the product until status changes
                        $product->delete();
                    }
                } else {
                    $qty = (int) ($item['quantity'] ?? 0);

                    \App\Models\WarehouseEntryItem::create([
                        'warehouse_entry_id' => $entry->id,
                        'shop_product_id'    => $item['shop_product_id'],
                        'quantity'           => $qty,
                        'unit_cost'          => $item['unit_cost'] ?? null,
                        'notes'              => $item['item_notes'] ?? null,
                    ]);

                    if ($status === 'completed' && $qty > 0) {
                        \App\Models\Shop\Product::where('id', $item['shop_product_id'])
                            ->increment('qty', $qty);
                    }
                }
            }
        });

        Notification::make()
            ->title('Warehouse entry saved successfully')
            ->success()
            ->send();

        $this->form->fill([
            'received_at' => now(),
            'status'      => 'pending',
        ]);
    }
}
