<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'note',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class, 'customer_order_id');
    }
}
