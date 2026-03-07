<?php

namespace App\Http\Controllers\Admin\Invoices;

use App\Models\Product;

class CreateController
{
    public function __invoke()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('admin.invoices.create', compact('products'));
    }
}
