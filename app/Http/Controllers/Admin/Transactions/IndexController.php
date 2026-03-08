<?php

namespace App\Http\Controllers\Admin\Transactions;

use Illuminate\Http\RedirectResponse;

class IndexController
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('admin.customer_orders.index');
    }
}
