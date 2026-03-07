<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Application\UseCases\Transactions\CreateDraftCustomerTransactionUseCase;
use App\Http\Requests\Admin\Transactions\StoreRequest;

class StoreController
{
    public function __invoke(StoreRequest $request, CreateDraftCustomerTransactionUseCase $useCase)
    {
        $data = $request->validated();

        $transactionId = $useCase->execute([
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
            ->route('admin.transactions.index')
            ->with('status', "Draft transaksi #{$transactionId} tersimpan.");
    }
}
