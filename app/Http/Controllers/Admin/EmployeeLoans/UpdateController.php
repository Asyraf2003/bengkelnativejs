<?php

namespace App\Http\Controllers\Admin\EmployeeLoans;

use App\Http\Requests\Admin\EmployeeLoans\UpdateRequest;
use App\Models\EmployeeLoan;
use Illuminate\Support\Facades\DB;

class UpdateController
{
    public function __invoke(int $loan, UpdateRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($loan, $data) {
            $loanRow = EmployeeLoan::query()
                ->withSum('payments as paid_total', 'amount')
                ->lockForUpdate()
                ->findOrFail($loan);

            $paidTotal = (int) ($loanRow->paid_total ?? 0);
            $newAmount = (int) $data['amount'];

            // guard: total pinjaman tidak boleh lebih kecil dari total yang sudah dibayar
            if ($newAmount < $paidTotal) {
                throw new \DomainException("Amount tidak boleh < total pembayaran ({$paidTotal}).");
            }

            $loanRow->update([
                'employee_id' => (int) $data['employee_id'],
                'loaned_at'   => $data['loaned_at'],
                'amount'      => $newAmount,
                'note'        => $data['note'],
            ]);
        });

        return redirect()->route('admin.employee_loans.index')
            ->with('status', 'Pinjaman ter-update.');
    }
}
