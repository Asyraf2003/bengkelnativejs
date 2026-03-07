<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Application\UseCases\Transactions\RefundCustomerTransactionUseCase;
use App\Http\Requests\Admin\Transactions\RefundRequest;
use App\Models\CustomerTransaction;

class RefundController
{
    public function __invoke(
        RefundRequest $request,
        CustomerTransaction $transaction,
        RefundCustomerTransactionUseCase $useCase
    ) {
        $transaction->load(['lines.product']);

        if ($request->isMethod('get')) {
            $stockLines = $transaction->lines
                ->filter(fn ($line) => in_array($line->kind, ['product_sale', 'service_product'], true))
                ->values();

            return view('admin.transactions.refund', [
                'transaction' => $transaction,
                'stockLines' => $stockLines,
            ]);
        }

        $data = $request->validated();

        $useCase->execute([
            'transaction_id' => (int) $transaction->id,
            'refunded_at' => (string) $data['refunded_at'],
            'refund_amount' => (int) $data['refund_amount'],
            'items' => collect($data['items'])
                ->map(fn (array $item): array => [
                    'line_id' => (int) $item['line_id'],
                    'qty' => (int) $item['qty'],
                ])
                ->values()
                ->all(),
        ]);

        return redirect()
            ->route('admin.transactions.index')
            ->with('status', "Refund transaksi #{$transaction->id} berhasil disimpan.");
    }
}
