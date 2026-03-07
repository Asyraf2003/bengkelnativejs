<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Models\CustomerTransaction;

class ShowController
{
    public function __invoke(CustomerTransaction $transaction)
    {
        $transaction->load([
            'lines.product:id,code,name',
        ]);

        return view('admin.transactions.show', compact('transaction'));
    }
}
