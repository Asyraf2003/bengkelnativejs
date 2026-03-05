<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoanPayment extends Model
{
    protected $fillable = [
        'employee_loan_id',
        'paid_at',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'employee_loan_id' => 'integer',
            'paid_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }
}
