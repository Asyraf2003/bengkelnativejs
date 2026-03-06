<?php

namespace App\Http\Controllers\Admin\Salaries;

use App\Models\Employee;

class CreateController
{
    public function __invoke()
    {
        $employees = Employee::query()->orderBy('name')->get(['id','name']);

        return view('admin.salaries.create', compact('employees'));
    }
}
