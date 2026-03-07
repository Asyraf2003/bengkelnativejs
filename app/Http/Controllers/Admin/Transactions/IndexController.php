<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Models\CustomerTransaction;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $status = $request->query('status');
        $from = $request->query('from');
        $to = $request->query('to');
        $q = trim((string) $request->query('q', ''));

        $rows = CustomerTransaction::query()
            ->with([
                'lines.product:id,name,code',
            ])
            ->withCount('lines')
            ->withCount([
                'lines as refundable_stock_lines_count' => function ($qb) {
                    $qb->whereIn('kind', ['product_sale', 'service_product']);
                },
            ])
            ->when($status, fn ($qb) => $qb->where('status', $status))
            ->when($from, fn ($qb) => $qb->whereDate('transacted_at', '>=', $from))
            ->when($to, fn ($qb) => $qb->whereDate('transacted_at', '<=', $to))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('customer_name', 'like', "%{$q}%")
                        ->orWhere('note', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.transactions.index', compact('rows', 'status', 'from', 'to', 'q'));
    }
}
