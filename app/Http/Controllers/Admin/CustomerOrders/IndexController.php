<?php

namespace App\Http\Controllers\Admin\CustomerOrders;

use App\Models\CustomerOrder;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $q = trim((string) $request->query('q', ''));

        $rows = CustomerOrder::query()
            ->withCount('transactions')
            ->when($from, fn ($qb) => $qb->whereDate('created_at', '>=', $from))
            ->when($to, fn ($qb) => $qb->whereDate('created_at', '<=', $to))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('customer_name', 'like', "%{$q}%")
                        ->orWhere('note', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customer_orders.index', compact('rows', 'from', 'to', 'q'));
    }
}
