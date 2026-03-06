<?php

namespace App\Http\Controllers\Admin\EmployeeLoanPayments;

use App\Models\EmployeeLoan;

class CreateController
{
    public function __invoke(int $loan)
    {
        $loan = EmployeeLoan::query()
            ->with('employee')
            ->withSum('payments as paid_total', 'amount')
            ->findOrFail($loan);

        return view('admin.employee_loan_payments.create', compact('loan'));
    }
}
