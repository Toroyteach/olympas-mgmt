<?php

namespace App\Filament\Resources\WarehouseEntries\Pages;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\WarehouseEntries\WarehouseEntryResource;
use App\Models\Shop\Product;
use App\Models\WarehouseEntryItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Filament\Support\Enums\Width;
use Promethys\Revive\Models\RecycleBinItem;

class EditWarehouseEntry extends EditRecord
{
    protected static string $resource = WarehouseEntryResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    // ── Pre-populate the repeater with existing items on load ─────────────────
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record
            ->items()
            ->withTrashed()
            ->with(['product' => fn($q) => $q->withTrashed()])
            ->get()
            ->map(fn($item) => [
                'product_mode'                  => $item->product_created_here ? 'new' : 'existing',
                'shop_product_id'               => $item->product_created_here ? null : $item->shop_product_id,
                'new_product_name'              => $item->product?->name,
                'new_product_slug'              => $item->product?->slug,
                'new_product_sku'               => $item->product?->sku,
                'new_product_barcode'           => $item->product?->barcode,
                'new_product_description'       => $item->product?->description,
                'new_product_price'             => $item->product?->price,
                'new_product_cost'              => $item->product?->cost,
                'new_product_is_visible'        => $item->product?->is_visible ?? true,
                'new_product_requires_shipping' => $item->product?->requires_shipping ?? false,
                'new_product_backorder'         => $item->product?->backorder ?? false,
                'new_product_published_at'      => $item->product?->published_at,
                'new_product_brand_id'          => $item->product?->shop_brand_id,
                'new_quantity'                  => $item->product_created_here ? $item->quantity : null,
                'new_unit_cost'                 => $item->product_created_here ? $item->unit_cost : null,
                'quantity'                      => $item->product_created_here ? null : $item->quantity,
                'unit_cost'                     => $item->product_created_here ? null : $item->unit_cost,
                'item_notes'                    => $item->notes,
            ])
            ->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            /** @var \App\Models\WarehouseEntry $record */
            $oldStatus = $record->status;
            $newStatus = $data['status'];

            $newItems = $data['items'] ?? [];
            unset($data['items']);

            $oldItems = WarehouseEntryItem::withTrashed()
                ->where('warehouse_entry_id', $record->id)
                ->with(['product' => fn($q) => $q->withTrashed()])
                ->get()
                ->keyBy('shop_product_id');

            // Revert stock from old completed items
            if ($oldStatus === 'completed') {
                foreach ($oldItems as $oldItem) {
                    if ($oldItem->product && $oldItem->quantity > 0) {
                        Product::withTrashed()
                            ->where('id', $oldItem->shop_product_id)
                            ->decrement('qty', $oldItem->quantity);
                    }
                }
            }

            // Force delete old items to make way for the new state
            WarehouseEntryItem::withTrashed()
                ->where('warehouse_entry_id', $record->id)
                ->forceDelete();

            // Update entry header
            $record->update($data);

            // Recreate items with updated logic
            foreach ($newItems as $item) {
                $this->createItem($record, $item, $newStatus);
            }

            return $record;
        });
    }

    private function createItem(
        \App\Models\WarehouseEntry $entry,
        array $item,
        string $status
    ): void {
        $mode = $item['product_mode'] ?? 'existing';

        if ($mode === 'new') {
            $slug = $item['new_product_slug'] ?? Str::slug($item['new_product_name'] ?? '');

            $product = Product::withTrashed()->where('slug', $slug)->first();

            if ($product) {
                // Restore if previously soft-deleted, clear from recycle bin
                if ($product->trashed()) {
                    $product->restore();
                    $this->clearRecycleBinEntry($product);
                }

                $product->update([
                    'name'              => $item['new_product_name'],
                    'sku'               => $item['new_product_sku'] ?? null,
                    'barcode'           => $item['new_product_barcode'] ?? null,
                    'description'       => $item['new_product_description'] ?? null,
                    'price'             => $item['new_product_price'],
                    'cost'              => $item['new_product_cost'] ?? null,
                    'is_visible'        => $item['new_product_is_visible'] ?? true,
                    'requires_shipping' => $item['new_product_requires_shipping'] ?? false,
                    'backorder'         => $item['new_product_backorder'] ?? false,
                    'published_at'      => $item['new_product_published_at'] ?? now(),
                    'shop_brand_id'     => $item['new_product_brand_id'] ?? null,
                ]);
            } else {
                $product = Product::create([
                    'name'              => $item['new_product_name'],
                    'slug'              => $slug,
                    'sku'               => $item['new_product_sku'] ?? null,
                    'barcode'           => $item['new_product_barcode'] ?? null,
                    'description'       => $item['new_product_description'] ?? null,
                    'price'             => $item['new_product_price'],
                    'cost'              => $item['new_product_cost'] ?? null,
                    'qty'               => 0, // Base qty handles increment later
                    'is_visible'        => $item['new_product_is_visible'] ?? true,
                    'requires_shipping' => $item['new_product_requires_shipping'] ?? false,
                    'backorder'         => $item['new_product_backorder'] ?? false,
                    'published_at'      => $item['new_product_published_at'] ?? now(),
                    'shop_brand_id'     => $item['new_product_brand_id'] ?? null,
                ]);
            }

            if (!empty($item['new_product_categories'])) {
                $product->categories()->sync($item['new_product_categories']);
            }

            $qty = (int) ($item['new_quantity'] ?? 0);

            WarehouseEntryItem::create([
                'warehouse_entry_id'   => $entry->id,
                'shop_product_id'      => $product->id,
                'quantity'             => $qty,
                'unit_cost'            => $item['new_unit_cost'] ?? null,
                'notes'                => null,
                'product_created_here' => true,
            ]);

            // Handle status logic for NEW products
            if ($status === 'completed') {
                if ($qty > 0) {
                    $product->increment('qty', $qty);
                }
            } else {
                // Soft delete to hide it from the storefront until completed
                if (!$product->trashed()) {
                    $product->delete();
                }
            }
        } else {
            // Logic for EXISTING products
            $qty = (int) ($item['quantity'] ?? 0);

            WarehouseEntryItem::create([
                'warehouse_entry_id'   => $entry->id,
                'shop_product_id'      => $item['shop_product_id'],
                'quantity'             => $qty,
                'unit_cost'            => $item['unit_cost'] ?? null,
                'notes'                => $item['item_notes'] ?? null,
                'product_created_here' => false,
            ]);

            if ($status === 'completed' && $qty > 0) {
                Product::where('id', $item['shop_product_id'])->increment('qty', $qty);
            }
        }
    }

    private function clearRecycleBinEntry(\Illuminate\Database\Eloquent\Model $model): void
    {
        RecycleBinItem::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->delete();
    }
}
