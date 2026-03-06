<?php

namespace App\Http\Controllers\Admin\EmployeeLoans;

use App\Models\EmployeeLoan;

class DeleteController
{
    public function __invoke(int $loan)
    {
        $loan = EmployeeLoan::query()->findOrFail($loan);

        // payments cascadeOnDelete -> ikut terhapus
        $loan->delete();

        return redirect()->route('admin.employee_loans.index')
            ->with('status', 'Pinjaman terhapus.');
    }
}
