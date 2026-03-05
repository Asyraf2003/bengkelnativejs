<?php

namespace App\Http\Controllers\Admin\Inventory\Adjustments;

use App\Application\UseCases\Inventory\AdjustStockUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController
{
    public function __invoke(Request $request, AdjustStockUseCase $useCase)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty_delta'  => ['required', 'integer', 'not_in:0'],
            'reason'     => ['required', 'string', 'min:3'],
        ]);

        $useCase->execute([
            'product_id' => (int) $data['product_id'],
            'qty_delta'  => (int) $data['qty_delta'],
            'reason'     => (string) $data['reason'],
            'user_id'    => (int) Auth::id(),
        ]);

        return redirect()->route('admin.products.index')
            ->with('status', 'Stock adjustment tersimpan.');
    }
}
