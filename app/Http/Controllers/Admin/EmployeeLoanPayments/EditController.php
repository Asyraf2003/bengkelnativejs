<?php

namespace App\Http\Controllers\Admin\EmployeeLoanPayments;

use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;

class EditController
{
    public function __invoke(int $loan, int $payment)
    {
        $loan = EmployeeLoan::query()
            ->with('employee')
            ->withSum('payments as paid_total', 'amount')
            ->findOrFail($loan);

        $payment = EmployeeLoanPayment::query()->findOrFail($payment);

        if ((int) $payment->employee_loan_id !== (int) $loan->id) {
            abort(404);
        }

        return view('admin.employee_loans.payments.edit', compact('loan','payment'));
    }
}
