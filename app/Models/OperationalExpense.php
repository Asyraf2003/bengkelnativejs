<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalExpense extends Model
{
    protected $fillable = [
        'name',
        'spent_at',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'spent_at' => 'date',
            'amount' => 'integer',
        ];
    }
}
