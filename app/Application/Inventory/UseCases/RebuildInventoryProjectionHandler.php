<?php

declare(strict_types=1);

namespace App\Application\Inventory\UseCases;

use App\Application\Shared\DTO\Result;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Inventory\ProductInventory\ProductInventory;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use App\Ports\Out\Inventory\ProductInventoryProjectionWriterPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RebuildInventoryProjectionHandler
{
    public function __construct(
        private readonly InventoryMovementReaderPort $inventoryMovements,
        private readonly ProductInventoryProjectionWriterPort $inventoryProjection,
        private readonly TransactionManagerPort $transactions,
    ) {
    }

    public function handle(): Result
    {
        $transactionStarted = false;

        try {
            $this->transactions->begin();
            $transactionStarted = true;

            $movements = $this->inventoryMovements->getAll();
            $inventories = $this->rebuildProjection($movements);

            $this->inventoryProjection->replaceAll($inventories);

            $this->transactions->commit();

            return Result::success(
                [
                    'total_movements' => count($movements),
                    'total_products' => count($inventories),
                    'products' => array_map(
                        static fn (ProductInventory $inventory): array => [
                            'product_id' => $inventory->productId(),
                            'qty_on_hand' => $inventory->qtyOnHand(),
                        ],
                        $inventories,
                    ),
                ],
                'Inventory projection berhasil dibangun ulang.'
            );
        } catch (DomainException $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            return Result::failure(
                $e->getMessage(),
                ['inventory' => ['INVALID_INVENTORY_PROJECTION']]
            );
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param list<InventoryMovement> $movements
     * @return list<ProductInventory>
     */
    private function rebuildProjection(array $movements): array
    {
        /** @var array<string, int> $qtyByProduct */
        $qtyByProduct = [];

        foreach ($movements as $movement) {
            $productId = $movement->productId();

            if (array_key_exists($productId, $qtyByProduct) === false) {
                $qtyByProduct[$productId] = 0;
            }

            $qtyByProduct[$productId] += $movement->qtyDelta();
        }

        ksort($qtyByProduct);

        $inventories = [];

        foreach ($qtyByProduct as $productId => $qtyOnHand) {
            $inventories[] = ProductInventory::create($productId, $qtyOnHand);
        }

        return $inventories;
    }
}
