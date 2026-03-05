<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = ['name'];

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class, 'employee_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class, 'employee_id');
    }
}
