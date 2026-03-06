<?php

namespace App\Http\Controllers\Admin\OperationalExpenses;

use App\Http\Requests\Admin\OperationalExpenses\StoreRequest;
use App\Models\OperationalExpense;

class StoreController
{
    public function __invoke(StoreRequest $request)
    {
        $data = $request->validated();

        OperationalExpense::query()->create([
            'name'     => $data['name'],
            'spent_at' => $data['spent_at'],
            'amount'   => (int) $data['amount'],
            'note'     => $data['note'],
        ]);

        return redirect()->route('admin.operational_expenses.index')
            ->with('status', 'Operasional tersimpan.');
    }
}
