<?php

namespace App\Http\Controllers\Admin\Employees;

class CreateController
{
    public function __invoke()
    {
        return view('admin.employees.create');
    }
}
