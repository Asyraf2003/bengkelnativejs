<?php

namespace App\Http\Controllers\Admin\Salaries;

use App\Http\Requests\Admin\Salaries\UpdateRequest;
use App\Models\Salary;

class UpdateController
{
    public function __invoke(int $salary, UpdateRequest $request)
    {
        $salary = Salary::query()->findOrFail($salary);

        $data = $request->validated();

        $salary->update([
            'employee_id' => (int) $data['employee_id'],
            'paid_at'     => $data['paid_at'],
            'amount'      => (int) $data['amount'],
            'note'        => $data['note'],
        ]);

        return redirect()->route('admin.salaries.index')
            ->with('status', 'Gaji ter-update.');
    }
}
