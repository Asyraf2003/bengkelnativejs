<?php

namespace App\Http\Controllers\Admin\CustomerOrders;

use App\Models\CustomerOrder;

class ShowController
{
    public function __invoke(CustomerOrder $customerOrder)
    {
        $customerOrder->load([
            'transactions' => function ($qb) {
                $qb->with([
                    'lines.product:id,code,name',
                ])
                ->withCount('lines')
                ->withCount([
                    'lines as refundable_stock_lines_count' => function ($lineQb) {
                        $lineQb->whereIn('kind', ['product_sale', 'service_product']);
                    },
                ])
                ->orderByDesc('transacted_at')
                ->orderByDesc('id');
            },
        ]);

        return view('admin.customer_orders.show', compact('customerOrder'));
    }
}
