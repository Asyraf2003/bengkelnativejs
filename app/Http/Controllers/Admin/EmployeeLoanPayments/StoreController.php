<?php

namespace App\Http\Controllers\Admin\EmployeeLoanPayments;

use App\Http\Requests\Admin\EmployeeLoanPayments\StoreRequest;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use Illuminate\Support\Facades\DB;

class StoreController
{
    public function __invoke(int $loan, StoreRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($loan, $data) {
            $loanRow = EmployeeLoan::query()
                ->withSum('payments as paid_total', 'amount')
                ->lockForUpdate()
                ->findOrFail($loan);

            $paidTotal = (int) ($loanRow->paid_total ?? 0);
            $remaining = (int) $loanRow->amount - $paidTotal;

            $amount = (int) $data['amount'];
            if ($amount > $remaining) {
                throw new \DomainException("Pembayaran melebihi sisa. remaining={$remaining}");
            }

            EmployeeLoanPayment::query()->create([
                'employee_loan_id' => $loanRow->id,
                'paid_at'          => $data['paid_at'],
                'amount'           => $amount,
                'note'             => $data['note'],
            ]);
        });

        return redirect()->route('admin.employee_loan_payments.index', $loan)
            ->with('status', 'Pembayaran tersimpan.');
    }
}
