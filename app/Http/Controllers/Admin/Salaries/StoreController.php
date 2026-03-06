<?php

namespace App\Http\Controllers\Admin\Salaries;

use App\Http\Requests\Admin\Salaries\StoreRequest;
use App\Models\Salary;

class StoreController
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        Salary::query()->create([
            'employee_id' => (int) $data['employee_id'],
            'paid_at'     => $data['paid_at'],
            'amount'      => (int) $data['amount'],
            'note'        => $data['note'], // UI wajib
        ]);

        return redirect()->route('admin.salaries.index')
            ->with('status', 'Gaji tersimpan.');
    }
}
