<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseEntryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_entry_id',
        'shop_product_id',
        'quantity',
        'unit_cost',
        'notes',
        'product_created_here',
    ];

    protected $casts = [
        'quantity'  => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(WarehouseEntry::class, 'warehouse_entry_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Shop\Product::class, 'shop_product_id');
    }
}
