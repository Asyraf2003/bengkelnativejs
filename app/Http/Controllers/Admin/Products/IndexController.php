<?php

namespace App\Http\Controllers\Admin\Products;

use App\Models\Product;
use Illuminate\Http\Request;

class IndexController
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with('inventory')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%")
                      ->orWhere('brand', 'like', "%{$q}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'q'));
    }
}
