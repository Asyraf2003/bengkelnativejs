<?php

declare(strict_types=1);

namespace App\Adapters\Out\Inventory;

use App\Core\Inventory\ProductInventory\ProductInventory;
use App\Ports\Out\Inventory\ProductInventoryProjectionWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseProductInventoryProjectionWriterAdapter implements ProductInventoryProjectionWriterPort
{
    /**
     * @param list<ProductInventory> $inventories
     */
    public function replaceAll(array $inventories): void
    {
        DB::table('product_inventory')->delete();

        if ($inventories === []) {
            return;
        }

        DB::table('product_inventory')->insert(
            array_map(
                static fn (ProductInventory $inventory): array => [
                    'product_id' => $inventory->productId(),
                    'qty_on_hand' => $inventory->qtyOnHand(),
                ],
                $inventories,
            )
        );
    }
}
