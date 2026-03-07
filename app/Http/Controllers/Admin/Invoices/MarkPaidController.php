<?php

namespace App\Http\Controllers\Admin\Invoices;

use App\Application\UseCases\Invoices\MarkPaidSupplierInvoiceUseCase;
use App\Http\Requests\Admin\Invoices\MarkPaidRequest;
use App\Models\SupplierInvoice;

class MarkPaidController
{
    public function __invoke(
        MarkPaidRequest $request,
        SupplierInvoice $invoice,
        MarkPaidSupplierInvoiceUseCase $useCase
    ) {
        $data = $request->validated();

        $useCase->execute([
            'invoice_id' => (int) $invoice->id,
            'paid_at' => (string) $data['paid_at'],
        ]);

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('status', "Faktur supplier #{$invoice->id} berhasil ditandai paid.");
    }
}
