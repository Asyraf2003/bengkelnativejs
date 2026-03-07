<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Application\UseCases\Transactions\DeleteDraftCustomerTransactionLineUseCase;
use App\Models\CustomerTransaction;
use App\Models\CustomerTransactionLine;

class DeleteLineController
{
    public function __invoke(
        CustomerTransaction $transaction,
        CustomerTransactionLine $line,
        DeleteDraftCustomerTransactionLineUseCase $useCase
    ) {
        if ((int) $line->customer_transaction_id !== (int) $transaction->id) {
            abort(404);
        }

        $useCase->execute((int) $transaction->id, (int) $line->id);

        return redirect()
            ->route('admin.transactions.index')
            ->with('status', "Line #{$line->id} dari draft transaksi #{$transaction->id} berhasil dihapus.");
    }
}
