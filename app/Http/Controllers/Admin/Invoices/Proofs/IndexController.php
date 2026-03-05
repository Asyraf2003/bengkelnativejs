<?php

namespace App\Http\Controllers\Admin\Invoices\Proofs;

use App\Models\SupplierInvoice;

class IndexController
{
    public function __invoke(int $invoice)
    {
        $invoice = SupplierInvoice::query()
            ->with(['media' => fn ($q) => $q->orderByDesc('uploaded_at')])
            ->findOrFail($invoice);

        return view('admin.invoices.proofs.index', compact('invoice'));
    }
}
