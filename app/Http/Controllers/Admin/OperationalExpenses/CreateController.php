<?php

namespace App\Http\Controllers\Admin\OperationalExpenses;

class CreateController
{
    public function __invoke()
    {
        return view('admin.operational_expenses.create');
    }
}
