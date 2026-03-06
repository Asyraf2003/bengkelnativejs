<?php

namespace App\Http\Controllers\Admin\OperationalExpenses;

use App\Models\OperationalExpense;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');
        $q    = trim((string) $request->query('q', ''));

        $rows = OperationalExpense::query()
            ->when($from, fn($qb) => $qb->whereDate('spent_at', '>=', $from))
            ->when($to, fn($qb) => $qb->whereDate('spent_at', '<=', $to))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('name','like',"%{$q}%")
                      ->orWhere('note','like',"%{$q}%");
                });
            })
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.operational_expenses.index', compact('rows','from','to','q'));
    }
}
