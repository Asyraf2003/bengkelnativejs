<?php

namespace App\Http\Controllers\Admin\OperationalExpenses;

use App\Http\Requests\Admin\OperationalExpenses\UpdateRequest;
use App\Models\OperationalExpense;

class UpdateController
{
    public function __invoke(int $expense, UpdateRequest $request)
    {
        $expense = OperationalExpense::query()->findOrFail($expense);

        $data = $request->validated();

        $expense->update([
            'name'     => $data['name'],
            'spent_at' => $data['spent_at'],
            'amount'   => (int) $data['amount'],
            'note'     => $data['note'],
        ]);

        return redirect()->route('admin.operational_expenses.index')
            ->with('status', 'Operasional ter-update.');
    }
}
