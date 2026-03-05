<?php

namespace App\Http\Controllers\Admin\Products;

use App\Models\Product;

class EditController
{
    public function __invoke(int $product)
    {
        $product = Product::query()->findOrFail($product);
        return view('admin.products.edit', compact('product'));
    }
}
