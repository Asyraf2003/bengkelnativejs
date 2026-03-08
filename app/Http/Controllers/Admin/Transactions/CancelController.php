<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Application\UseCases\Transactions\CancelDraftCustomerTransactionUseCase;
use App\Models\CustomerTransaction;

class CancelController
{
    public function __invoke(
        CustomerTransaction $transaction,
        CancelDraftCustomerTransactionUseCase $useCase
    ) {
        $useCase->execute((int) $transaction->id);

        return redirect()
            ->route('admin.customer_orders.show', $transaction->customer_order_id)
            ->with('status', "Kasus draft #{$transaction->id} berhasil dibatalkan.");
    }
}
