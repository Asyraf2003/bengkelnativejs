<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Application\UseCases\Transactions\UpdateDraftCustomerTransactionUseCase;
use App\Http\Requests\Admin\Transactions\UpdateRequest;
use App\Models\CustomerTransaction;

class UpdateController
{
    public function __invoke(
        UpdateRequest $request,
        CustomerTransaction $transaction,
        UpdateDraftCustomerTransactionUseCase $useCase
    ) {
        $data = $request->validated();

        $useCase->execute([
            'transaction_id' => (int) $transaction->id,
            'customer_name' => (string) $data['customer_name'],
            'transacted_at' => (string) $data['transacted_at'],
            'note' => $data['note'] ?? null,
            'lines' => collect($data['lines'])
                ->map(function (array $line): array {
                    return [
                        'kind' => (string) $line['kind'],
                        'product_id' => isset($line['product_id']) && $line['product_id'] !== '' ? (int) $line['product_id'] : null,
                        'qty' => isset($line['qty']) && $line['qty'] !== '' ? (int) $line['qty'] : null,
                        'amount' => (int) $line['amount'],
                        'note' => $line['note'] ?? null,
                    ];
                })
                ->values()
                ->all(),
        ]);

        return redirect()
            ->route('admin.customer_orders.show', $transaction->customer_order_id)
            ->with('status', "Draft kasus #{$transaction->id} berhasil diperbarui.");
    }
}
