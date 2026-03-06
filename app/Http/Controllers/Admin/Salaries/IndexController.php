<?php

namespace App\Http\Controllers\Admin\Salaries;

use App\Models\Employee;
use App\Models\Salary;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');
        $employeeId = $request->query('employee_id');

        $rows = Salary::query()
            ->with('employee')
            ->when($from, fn($qb) => $qb->whereDate('paid_at', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('paid_at', '<=', $to))
            ->when($employeeId, fn($qb) => $qb->where('employee_id', (int) $employeeId))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::query()->orderBy('name')->get(['id','name']);

        return view('admin.salaries.index', compact('rows','from','to','employeeId','employees'));
    }
}
