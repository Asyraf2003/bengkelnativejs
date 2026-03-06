<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Models\Product;

class StockController
{
    public function __invoke()
    {
        $rows = Product::query()
            ->with('inventory')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.reports.stock', compact('rows'));
    }
}
