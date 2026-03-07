<?php

namespace App\Http\Controllers\Admin\Invoices;

use App\Models\SupplierInvoice;

class ShowController
{
    public function __invoke(SupplierInvoice $invoice)
    {
        $invoice->load([
            'items.product:id,code,name',
            'media',
        ]);

        return view('admin.invoices.show', compact('invoice'));
    }
}
