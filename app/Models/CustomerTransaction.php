<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerTransaction extends Model
{
    protected $fillable = [
        'customer_order_id',
        'customer_name',
        'status',
        'transacted_at',
        'paid_at',
        'refunded_at',
        'refund_amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'customer_order_id' => 'integer',
            'transacted_at'     => 'date',
            'paid_at'           => 'date',
            'refunded_at'       => 'date',
            'refund_amount'     => 'integer',
        ];
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CustomerTransactionLine::class, 'customer_transaction_id');
    }
}
