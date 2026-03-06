<?php

namespace App\Http\Controllers\Admin\OperationalExpenses;

use App\Models\OperationalExpense;

class DeleteController
{
    public function __invoke(int $expense)
    {
        $expense = OperationalExpense::query()->findOrFail($expense);
        $expense->delete();

        return redirect()->route('admin.operational_expenses.index')
            ->with('status', 'Operasional terhapus.');
    }
}
