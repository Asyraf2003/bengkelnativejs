<?php

namespace App\Http\Controllers\Admin\Employees;

use App\Models\Employee;
use Illuminate\Database\QueryException;

class DeleteController
{
    public function __invoke(int $employee)
    {
        $employee = Employee::query()->findOrFail($employee);

        try {
            $employee->delete();
        } catch (QueryException $e) {
            // FK restrict: kalau sudah dipakai salaries/loans, delete harus ditolak
            return redirect()->route('admin.employees.index')
                ->with('status', 'Gagal hapus: karyawan sudah dipakai transaksi.');
        }

        return redirect()->route('admin.employees.index')
            ->with('status', 'Karyawan terhapus.');
    }
}
