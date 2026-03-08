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

        $alreadyRefunded = $transaction->refunded_at !== null
            || (int) $transaction->refund_amount > 0
            || $transaction->lines->contains(fn ($line) => (int) $line->refunded_qty > 0);

        if ($alreadyRefunded) {
            return redirect()
                ->route('admin.customer_orders.show', $transaction->customer_order_id)
                ->withErrors(['refund' => 'Kasus ini sudah pernah direfund. Refund hanya boleh sekali per kasus.']);
        }

        if ($request->isMethod('get')) {
            $stockLines = $transaction->lines
                ->filter(fn ($line) => in_array($line->kind, ['product_sale', 'service_product'], true))
                ->filter(fn ($line) => ((int) $line->qty - (int) $line->refunded_qty) > 0)
                ->values();

            return view('admin.transactions.refund', [
                'transaction' => $transaction,
                'stockLines' => $stockLines,
            ]);
        }

        $data = $request->validated();

        $items = collect($data['items'])
            ->map(fn (array $item): array => [
                'line_id' => (int) $item['line_id'],
                'qty' => (int) ($item['qty'] ?? 0),
            ])
            ->filter(fn (array $item) => $item['qty'] > 0)
            ->values()
            ->all();

        $useCase->execute([
            'transaction_id' => (int) $transaction->id,
            'refunded_at' => (string) $data['refunded_at'],
            'refund_amount' => (int) $data['refund_amount'],
            'items' => $items,
        ]);

        return redirect()
            ->route('admin.customer_orders.show', $transaction->customer_order_id)
            ->with('status', "Refund kasus #{$transaction->id} berhasil disimpan.");
    }
}
