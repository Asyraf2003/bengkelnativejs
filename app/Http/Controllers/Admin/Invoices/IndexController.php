<?php

namespace App\Http\Controllers\Admin\Invoices;

use App\Models\SupplierInvoice;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $isPaid = $request->query('is_paid');
        $q = trim((string) $request->query('q', ''));

        $rows = SupplierInvoice::query()
            ->when($from, fn ($qb) => $qb->whereDate('delivered_at', '>=', $from))
            ->when($to, fn ($qb) => $qb->whereDate('delivered_at', '<=', $to))
            ->when($isPaid !== null && $isPaid !== '', fn ($qb) => $qb->where('is_paid', (bool) $isPaid))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('invoice_no', 'like', "%{$q}%")
                        ->orWhere('supplier_name', 'like', "%{$q}%")
                        ->orWhere('note', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('delivered_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.invoices.index', compact('rows', 'from', 'to', 'isPaid', 'q'));
    }
}
