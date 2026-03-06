<?php

namespace App\Http\Controllers\Admin\EmployeeLoans;

use App\Models\Employee;

class CreateController
{
    public function __invoke()
    {
        $employees = Employee::query()->orderBy('name')->get(['id','name']);

        return view('admin.employee_loans.create', compact('employees'));
    }
}
