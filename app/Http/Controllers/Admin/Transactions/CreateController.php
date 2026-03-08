<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Models\CustomerOrder;
use App\Models\Product;
use Illuminate\Http\Request;

class CreateController
{
    public function __invoke(Request $request)
    {
        $customerOrderId = $request->query('customer_order_id');
        $customerOrder = null;

        if ($customerOrderId !== null && $customerOrderId !== '') {
            $customerOrder = CustomerOrder::query()->findOrFail((int) $customerOrderId);
        }

        $products = Product::query()
            ->with('inventory')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get([
                'id',
                'code',
                'name',
                'sale_price',
                'is_active',
            ]);

        return view('admin.transactions.create', compact('products', 'customerOrder'));
    }
}
