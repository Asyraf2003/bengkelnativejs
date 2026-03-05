<?php

namespace App\Http\Controllers\Admin\Products;

use App\Application\UseCases\Products\CreateProductUseCase;
use App\Http\Requests\Admin\Products\StoreRequest;

class StoreController
{
    public function __invoke(StoreRequest $request, CreateProductUseCase $useCase)
    {
        $data = $request->validated();

        $useCase->execute([
            'code'       => (string) $data['code'],
            'name'       => (string) $data['name'],
            'brand'      => (string) $data['brand'],
            'size'       => (string) $data['size'],
            'sale_price' => (int) $data['sale_price'],
            'is_active'  => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('admin.products.index')->with('status', 'Produk tersimpan.');
    }
}
