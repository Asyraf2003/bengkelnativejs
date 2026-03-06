<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Models\SupplierInvoice;
use Carbon\CarbonImmutable;

class InvoiceDueSoonController
{
    public function __invoke()
    {
        $today = CarbonImmutable::today()->toDateString();
        $until = CarbonImmutable::today()->addDays(5)->toDateString();

        $rows = SupplierInvoice::query()
            ->whereDate('due_at', '>=', $today)
            ->whereDate('due_at', '<=', $until)
            ->orderBy('due_at')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.invoice_due_soon', compact('rows', 'today', 'until'));
    }
}
