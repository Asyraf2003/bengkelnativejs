<?php

namespace App\Http\Controllers\Admin\Transactions;

use App\Models\Product;

class CreateController
{
    public function __invoke()
    {
        $products = Product::query()
            ->with('inventory')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('admin.transactions.create', compact('products'));
    }
}
