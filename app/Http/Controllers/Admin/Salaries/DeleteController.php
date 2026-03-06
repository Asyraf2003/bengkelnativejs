<?php

namespace App\Http\Controllers\Admin\Salaries;

use App\Models\Salary;

class DeleteController
{
    public function __invoke(int $salary)
    {
        $salary = Salary::query()->findOrFail($salary);
        $salary->delete();

        return redirect()->route('admin.salaries.index')
            ->with('status', 'Gaji terhapus.');
    }
}
