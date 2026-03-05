<?php

namespace App\Application\UseCases\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProductUseCase
{
    /** @param array{code:string,name:string,brand:string,size:string,sale_price:int,is_active:bool} $input */
    public function execute(array $input): int
    {
        return DB::transaction(function () use ($input) {
            $p = Product::query()->create([
                'code'       => $input['code'],
                'name'       => $input['name'],
                'brand'      => $input['brand'],
                'size'       => $input['size'],
                'sale_price' => $input['sale_price'],
                'is_active'  => $input['is_active'],
            ]);

            // inventory row auto dibuat oleh Product::created hook (fase 1 step 1)
            return $p->id;
        });
    }
}
