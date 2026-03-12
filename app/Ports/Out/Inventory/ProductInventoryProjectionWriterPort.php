<?php

declare(strict_types=1);

namespace App\Ports\Out\Inventory;

use App\Core\Inventory\ProductInventory\ProductInventory;

interface ProductInventoryProjectionWriterPort
{
    /**
     * @param list<ProductInventory> $inventories
     */
    public function replaceAll(array $inventories): void;
}
