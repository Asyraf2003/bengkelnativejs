<?php

namespace App\Application\UseCases\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProductUseCase
{
    /** @param array{product_id:int,code:string,name:string,brand:string,size:string,sale_price:int,is_active:bool} $input */
    public function execute(array $input): void
    {
        DB::transaction(function () use ($input) {
            $p = Product::query()->findOrFail($input['product_id']);

            $p->update([
                'code'       => $input['code'],
                'name'       => $input['name'],
                'brand'      => $input['brand'],
                'size'       => $input['size'],
                'sale_price' => $input['sale_price'],
                'is_active'  => $input['is_active'],
            ]);
        });
    }
}
