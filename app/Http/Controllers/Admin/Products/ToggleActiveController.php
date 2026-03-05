<?php

namespace App\Http\Controllers\Admin\Products;

use App\Application\UseCases\Products\ToggleProductActiveUseCase;

class ToggleActiveController
{
    public function __invoke(int $product, ToggleProductActiveUseCase $useCase)
    {
        $useCase->execute($product);
        return redirect()->route('admin.products.index')->with('status', 'Status produk diubah.');
    }
}
