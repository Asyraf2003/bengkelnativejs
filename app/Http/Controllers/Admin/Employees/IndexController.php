<?php

namespace App\Http\Controllers\Admin\Employees;

use App\Models\Employee;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Employee::query()
            ->when($q !== '', fn ($qb) => $qb->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.employees.index', compact('rows', 'q'));
    }
}
