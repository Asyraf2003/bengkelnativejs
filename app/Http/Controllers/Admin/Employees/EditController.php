<?php

namespace App\Http\Controllers\Admin\Employees;

use App\Models\Employee;

class EditController
{
    public function __invoke(int $employee)
    {
        $employee = Employee::query()->findOrFail($employee);

        return view('admin.employees.edit', compact('employee'));
    }
}
