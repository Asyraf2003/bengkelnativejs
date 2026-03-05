<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'brand',
        'size',
        'sale_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $product) {
            // invariant fase 1: setiap produk wajib punya row inventory
            ProductInventory::query()->create([
                'product_id'   => $product->id,
                'on_hand_qty'  => 0,
                'reserved_qty' => 0,
                'avg_cost'     => 0,
            ]);
        });
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(ProductInventory::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_id');
    }
}
