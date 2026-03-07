<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Application\UseCases\Transactions\MarkPaidCustomerTransactionUseCase;
use App\Http\Requests\Admin\Transactions\MarkPaidRequest;
use App\Models\CustomerTransaction;

class MarkPaidController
{
    public function __invoke(
        MarkPaidRequest $request,
        CustomerTransaction $transaction,
        MarkPaidCustomerTransactionUseCase $useCase
    ) {
        $data = $request->validated();

        $useCase->execute([
            'transaction_id' => (int) $transaction->id,
            'paid_at' => (string) $data['paid_at'],
        ]);

        return redirect()
            ->route('admin.transactions.index')
            ->with('status', "Transaksi #{$transaction->id} berhasil di-mark paid.");
    }
}
