<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'supplier_name',
        'delivered_at',
        'due_at',
        'is_paid',
        'paid_at',
        'grand_total',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'date',
            'due_at'       => 'date',
            'paid_at'      => 'date',
            'is_paid'      => 'boolean',
            'grand_total'  => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class, 'supplier_invoice_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(SupplierInvoiceMedia::class, 'supplier_invoice_id');
    }
}
