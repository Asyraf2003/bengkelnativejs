<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductInventory extends Model
{
    protected $table = 'product_inventory';
    protected $primaryKey = 'product_id';
    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'on_hand_qty',
        'reserved_qty',
        'avg_cost',
    ];

    protected function casts(): array
    {
        return [
            'on_hand_qty'  => 'integer',
            'reserved_qty' => 'integer',
            'avg_cost'     => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getAvailableQtyAttribute(): int
    {
        return (int) $this->on_hand_qty - (int) $this->reserved_qty;
    }
}
