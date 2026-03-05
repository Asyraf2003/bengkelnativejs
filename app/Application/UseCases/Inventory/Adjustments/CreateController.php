<?php

namespace App\Http\Controllers\Admin\Inventory\Adjustments;

use App\Models\Product;

class CreateController
{
    public function __invoke()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'brand', 'size']);

        return view('admin.inventory.adjustments.create', compact('products'));
    }
}
