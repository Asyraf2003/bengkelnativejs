<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    protected $fillable = [
        'employee_id',
        'paid_at',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'paid_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
