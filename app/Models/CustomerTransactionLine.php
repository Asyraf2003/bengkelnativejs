<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTransactionLine extends Model
{
    protected $fillable = [
        'customer_transaction_id',
        'kind',
        'product_id',
        'qty',
        'amount',
        'cogs_amount',
        'sale_unit_cost',
        'refunded_qty',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'product_id'     => 'integer',
            'qty'            => 'integer',
            'amount'         => 'integer',
            'cogs_amount'    => 'integer',
            'sale_unit_cost' => 'integer',
            'refunded_qty'   => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(CustomerTransaction::class, 'customer_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function usesStock(): bool
    {
        return in_array($this->kind, ['product_sale', 'service_product'], true);
    }
}
