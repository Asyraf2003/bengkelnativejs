<?php

namespace App\Http\Controllers\Admin\EmployeeLoanPayments;

use App\Http\Requests\Admin\EmployeeLoanPayments\UpdateRequest;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use Illuminate\Support\Facades\DB;

class UpdateController
{
    public function __invoke(int $loan, int $payment, UpdateRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($loan, $payment, $data) {
            $loanRow = EmployeeLoan::query()
                ->lockForUpdate()
                ->findOrFail($loan);

            $payRow = EmployeeLoanPayment::query()
                ->lockForUpdate()
                ->findOrFail($payment);

            if ((int) $payRow->employee_loan_id !== (int) $loanRow->id) {
                abort(404);
            }

            // remaining dihitung tanpa payment ini (exclude)
            $paidOther = (int) EmployeeLoanPayment::query()
                ->where('employee_loan_id', $loanRow->id)
                ->where('id', '!=', $payRow->id)
                ->sum('amount');

            $remainingPlusThis = (int) $loanRow->amount - $paidOther;
            $newAmount = (int) $data['amount'];

            if ($newAmount > $remainingPlusThis) {
                throw new \DomainException("Pembayaran melebihi sisa. remaining_allow={$remainingPlusThis}");
            }

            $payRow->update([
                'paid_at' => $data['paid_at'],
                'amount'  => $newAmount,
                'note'    => $data['note'],
            ]);
        });

        return redirect()->route('admin.employee_loan_payments.index', $loan)
            ->with('status', 'Pembayaran ter-update.');
    }
}
