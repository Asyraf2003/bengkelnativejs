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
            ->route('admin.transactions.index')
            ->with('status', "Draft transaksi #{$transaction->id} berhasil dibatalkan.");
    }
}
