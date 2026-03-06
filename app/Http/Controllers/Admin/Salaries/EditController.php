<?php

namespace App\Http\Controllers\Admin\Salaries;

use App\Models\Employee;
use App\Models\Salary;

class EditController
{
    public function __invoke(int $salary)
    {
        $salary = Salary::query()->findOrFail($salary);
        $employees = Employee::query()->orderBy('name')->get(['id','name']);

        return view('admin.salaries.edit', compact('salary','employees'));
    }
}
