<?php

namespace App\Application\UseCases\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ToggleProductActiveUseCase
{
    public function execute(int $productId): void
    {
        DB::transaction(function () use ($productId) {
            $p = Product::query()->findOrFail($productId);
            $p->is_active = !$p->is_active;
            $p->save();
        });
    }
}
