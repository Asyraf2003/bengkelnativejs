<?php

namespace App\Http\Controllers\Admin\EmployeeLoanPayments;

use App\Models\EmployeeLoanPayment;

class DeleteController
{
    public function __invoke(int $loan, int $payment)
    {
        $payRow = EmployeeLoanPayment::query()->findOrFail($payment);

        if ((int) $payRow->employee_loan_id !== (int) $loan) {
            abort(404);
        }

        $payRow->delete();

        return redirect()->route('admin.employee_loan_payments.index', $loan)
            ->with('status', 'Pembayaran terhapus.');
    }
}
