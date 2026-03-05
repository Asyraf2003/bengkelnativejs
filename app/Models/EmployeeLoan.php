<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id',
        'loaned_at',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'loaned_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeeLoanPayment::class, 'employee_loan_id');
    }
}
