<?php

declare(strict_types=1);

namespace App\Application\Inventory\Services;

use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Inventory\ProductInventory\ProductInventory;

final class InventoryProjectionBuilder
{
    /**
     * @param list<InventoryMovement> $movements
     * @return list<ProductInventory>
     */
    public function build(array $movements): array
    {
        $qtyByProduct = [];
        foreach ($movements as $m) {
            $pId = $m->productId();
            $qtyByProduct[$pId] = ($qtyByProduct[$pId] ?? 0) + $m->qtyDelta();
        }

        ksort($qtyByProduct);

        return array_map(
            fn($pId, $qty) => ProductInventory::create($pId, $qty),
            array_keys($qtyByProduct),
            $qtyByProduct
        );
    }
}
