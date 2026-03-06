<?php

namespace App\Http\Controllers\Admin\Employees;

use App\Http\Requests\Admin\Employees\UpdateRequest;
use App\Models\Employee;

class UpdateController
{
    public function __invoke(int $employee, UpdateRequest $request)
    {
        $employee = Employee::query()->findOrFail($employee);

        $data = $request->validated();

        $employee->update([
            'name' => $data['name'],
        ]);

        return redirect()->route('admin.employees.index')
            ->with('status', 'Karyawan ter-update.');
    }
}
