<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Application\UseCases\Transactions\CreateDraftCustomerTransactionUseCase;
use App\Http\Requests\Admin\Transactions\StoreRequest;
use App\Models\CustomerOrder;
use App\Models\CustomerTransaction;

class StoreController
{
    public function __invoke(StoreRequest $request, CreateDraftCustomerTransactionUseCase $useCase)
    {
        $data = $request->validated();

        $customerOrderId = isset($data['customer_order_id']) && $data['customer_order_id'] !== ''
            ? (int) $data['customer_order_id']
            : null;

        $customerName = (string) $data['customer_name'];

        if ($customerOrderId) {
            $order = CustomerOrder::query()->findOrFail($customerOrderId);

            // source of truth parent tetap customer_orders
            $customerName = (string) $order->customer_name;
        }

        $transactionId = $useCase->execute([
            'customer_order_id' => $customerOrderId,
            'customer_name' => $customerName,
            'transacted_at' => (string) $data['transacted_at'],
            'note' => $data['note'] ?? null,
            'lines' => collect($data['lines'])
                ->map(function (array $line): array {
                    return [
                        'kind' => (string) $line['kind'],
                        'product_id' => isset($line['product_id']) && $line['product_id'] !== ''
                            ? (int) $line['product_id']
                            : null,
                        'qty' => isset($line['qty']) && $line['qty'] !== ''
                            ? (int) $line['qty']
                            : null,
                        'amount' => (int) $line['amount'],
                        'note' => $line['note'] ?? null,
                    ];
                })
                ->values()
                ->all(),
        ]);

        $transaction = CustomerTransaction::query()->findOrFail($transactionId);

        return redirect()
            ->route('admin.customer_orders.show', $transaction->customer_order_id)
            ->with('status', "Draft kasus #{$transaction->id} berhasil disimpan.");
    }
}
