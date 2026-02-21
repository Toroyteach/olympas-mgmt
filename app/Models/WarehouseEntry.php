<?php

namespace App\Models;

use App\Models\Shop\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseEntry extends BaseRecyclableModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'reference_number',
        'supplier_name',
        'supplier_invoice',
        'status',
        'notes',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (WarehouseEntry $entry): void {
            if (empty($entry->reference_number)) {
                $entry->reference_number = 'WE-' . strtoupper(uniqid());
            }
        });

        // ── Soft delete ───────────────────────────────────────────────────────────
        static::deleting(function (WarehouseEntry $entry): void {
            if ($entry->isForceDeleting()) {
                return; // handled by force-delete hook below
            }

            $items = $entry->items()->with('product')->get();

            foreach ($items as $item) {
                // Revert stock if entry was completed
                if ($entry->status === 'completed' && $item->quantity > 0) {
                    Product::where('id', $item->shop_product_id)
                        ->decrement('qty', $item->quantity);
                }

                // Soft-delete products that were created via this entry
                if ($item->product_created_here && $item->product) {
                    $item->product->delete();
                }
            }

            // Soft-delete the entry items too
            $entry->items()->delete();
        });

        // ── Force delete ──────────────────────────────────────────────────────────
        static::forceDeleting(function (WarehouseEntry $entry): void {
            $items = $entry->items()->withTrashed()->with('product')->get();

            foreach ($items as $item) {
                // Revert stock if entry was completed and product still exists
                if ($entry->status === 'completed' && $item->quantity > 0 && $item->product) {
                    $item->product->decrement('qty', $item->quantity);
                }

                // Force-delete products created via this entry
                if ($item->product_created_here && $item->product) {
                    $item->product->forceDelete();
                }

                // Force-delete the item itself
                $item->forceDelete();
            }
        });

        // ── Restore ───────────────────────────────────────────────────────────────
        static::restoring(function (WarehouseEntry $entry): void {
            $items = $entry->items()->withTrashed()->with('product')->get();

            foreach ($items as $item) {
                // Restore products that were created via this entry
                if ($item->product_created_here && $item->product) {
                    $item->product->restore();
                }

                // Restore item record
                $item->restore();

                // Re-apply stock if entry was completed
                if ($entry->status === 'completed' && $item->quantity > 0) {
                    Product::where('id', $item->shop_product_id)
                        ->increment('qty', $item->quantity);
                }
            }
        });
    }

    public function itemsWithTrashedProducts(): HasMany
    {
        return $this->hasMany(WarehouseEntryItem::class)
            ->with(['product' => fn($q) => $q->withTrashed()]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseEntryItem::class);
    }
}
