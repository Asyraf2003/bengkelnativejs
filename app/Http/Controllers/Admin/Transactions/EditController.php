<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Models\CustomerTransaction;
use App\Models\Product;

class EditController
{
    public function __invoke(CustomerTransaction $transaction)
    {
        if ($transaction->status !== 'draft') {
            abort(404);
        }

        $transaction->load([
            'lines.product:id,code,name,sale_price',
        ]);

        $lineProductIds = $transaction->lines
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $products = Product::query()
            ->where(function ($qb) use ($lineProductIds) {
                $qb->where('is_active', true);

                if (count($lineProductIds) > 0) {
                    $qb->orWhereIn('id', $lineProductIds);
                }
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get([
                'id',
                'code',
                'name',
                'sale_price',
                'is_active',
            ]);

        return view('admin.transactions.edit', compact('transaction', 'products'));
    }
}
