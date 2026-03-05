<?php

namespace App\Http\Controllers\Admin\Products;

use App\Application\UseCases\Products\UpdateProductUseCase;
use App\Http\Requests\Admin\Products\UpdateRequest;

class UpdateController
{
    public function __invoke(int $product, UpdateRequest $request, UpdateProductUseCase $useCase)
    {
        $data = $request->validated();

        $useCase->execute([
            'product_id' => $product,
            'code'       => (string) $data['code'],
            'name'       => (string) $data['name'],
            'brand'      => (string) $data['brand'],
            'size'       => (string) $data['size'],
            'sale_price' => (int) $data['sale_price'],
            'is_active'  => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('admin.products.index')->with('status', 'Produk ter-update.');
    }
}
