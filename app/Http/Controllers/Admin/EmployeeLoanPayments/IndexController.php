<?php

namespace App\Http\Controllers\Admin\EmployeeLoanPayments;

use App\Models\EmployeeLoan;

class IndexController
{
    public function __invoke(int $loan)
    {
        $loan = EmployeeLoan::query()
            ->with('employee')
            ->withSum('payments as paid_total', 'amount')
            ->findOrFail($loan);

        $payments = $loan->payments()
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.employee_loan_payments.index', compact('loan','payments'));
    }
}
