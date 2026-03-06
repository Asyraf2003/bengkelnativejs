<?php

namespace App\Http\Controllers\Admin\EmployeeLoans;

use App\Models\Employee;
use App\Models\EmployeeLoan;

class EditController
{
    public function __invoke(int $loan)
    {
        $loan = EmployeeLoan::query()
            ->withSum('payments as paid_total', 'amount')
            ->findOrFail($loan);

        $employees = Employee::query()->orderBy('name')->get(['id','name']);

        return view('admin.employee_loans.edit', compact('loan','employees'));
    }
}
