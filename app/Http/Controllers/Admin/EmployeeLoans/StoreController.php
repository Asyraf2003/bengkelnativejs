<?php

namespace App\Http\Controllers\Admin\EmployeeLoans;

use App\Http\Requests\Admin\EmployeeLoans\StoreRequest;
use App\Models\EmployeeLoan;

class StoreController
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        EmployeeLoan::query()->create([
            'employee_id' => (int) $data['employee_id'],
            'loaned_at'   => $data['loaned_at'],
            'amount'      => (int) $data['amount'],
            'note'        => $data['note'], // UI wajib
        ]);

        return redirect()->route('admin.employee_loans.index')
            ->with('status', 'Pinjaman tersimpan.');
    }
}
