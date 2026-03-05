<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'qty',
        'unit_cost',
        'ref_type',
        'ref_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'qty'       => 'integer',
            'unit_cost' => 'integer',
            'ref_id'    => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
