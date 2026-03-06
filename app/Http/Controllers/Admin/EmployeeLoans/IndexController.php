<?php

namespace App\Http\Controllers\Admin\EmployeeLoans;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');
        $employeeId = $request->query('employee_id');

        $rows = EmployeeLoan::query()
            ->with('employee')
            ->withSum('payments as paid_total', 'amount')
            ->when($from, fn($qb) => $qb->whereDate('loaned_at', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('loaned_at', '<=', $to))
            ->when($employeeId, fn($qb) => $qb->where('employee_id', (int) $employeeId))
            ->orderByDesc('loaned_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::query()->orderBy('name')->get(['id','name']);

        return view('admin.employee_loans.index', compact('rows','from','to','employeeId','employees'));
    }
}
