<?php

namespace App\Http\Controllers\Admin\Products;

class CreateController
{
    public function __invoke()
    {
        return view('admin.products.create');
    }
}
