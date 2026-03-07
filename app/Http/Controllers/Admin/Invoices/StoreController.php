<?php

namespace App\Http\Controllers\Admin\Invoices;

use App\Application\UseCases\Invoices\CreateSupplierInvoiceUseCase;
use App\Http\Requests\Admin\Invoices\StoreRequest;

class StoreController
{
    public function __invoke(StoreRequest $request, CreateSupplierInvoiceUseCase $useCase)
    {
        $data = $request->validated();

        $invoiceId = $useCase->execute([
            'invoice_no' => (string) $data['invoice_no'],
            'supplier_name' => (string) $data['supplier_name'],
            'delivered_at' => (string) $data['delivered_at'],
            'due_at' => $data['due_at'] ?? null,
            'note' => $data['note'] ?? null,
            'items' => collect($data['items'])
                ->map(fn (array $item) => [
                    'product_id' => (int) $item['product_id'],
                    'qty' => (int) $item['qty'],
                    'total_cost' => (int) $item['total_cost'],
                ])
                ->values()
                ->all(),
        ]);

        return redirect()
            ->route('admin.invoices.index')
            ->with('status', "Faktur supplier #{$invoiceId} tersimpan.");
    }
}
