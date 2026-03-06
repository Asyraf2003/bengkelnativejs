<?php

namespace App\Http\Controllers\Admin\Employees;

use App\Http\Requests\Admin\Employees\StoreRequest;
use App\Models\Employee;

class StoreController
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        Employee::query()->create([
            'name' => $data['name'],
        ]);

        return redirect()->route('admin.employees.index')
            ->with('status', 'Karyawan tersimpan.');
    }
}
