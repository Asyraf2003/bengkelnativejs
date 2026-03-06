<?php

namespace App\Http\Controllers\Admin\OperationalExpenses;

use App\Models\OperationalExpense;

class EditController
{
    public function __invoke(int $expense)
    {
        $expense = OperationalExpense::query()->findOrFail($expense);
        return view('admin.operational_expenses.edit', compact('expense'));
    }
}
