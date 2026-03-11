<?php

declare(strict_types=1);

namespace App\Ports\Out\Inventory;

use App\Core\Inventory\ProductInventory\ProductInventory;

interface ProductInventoryWriterPort
{
    public function upsert(ProductInventory $productInventory): void;
}
