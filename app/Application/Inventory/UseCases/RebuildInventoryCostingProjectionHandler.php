<?php

declare(strict_types=1);

namespace App\Application\Inventory\UseCases;

use App\Application\Shared\DTO\Result;
use App\Core\Inventory\Costing\ProductInventoryCosting;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use App\Ports\Out\Inventory\ProductInventoryCostingProjectionWriterPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RebuildInventoryCostingProjectionHandler
{
    public function __construct(
        private readonly InventoryMovementReaderPort $inventoryMovements,
        private readonly ProductInventoryCostingProjectionWriterPort $inventoryCostingProjection,
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
            $costings = $this->rebuildProjection($movements);

            $this->inventoryCostingProjection->replaceAll($costings);

            $this->transactions->commit();

            return Result::success(
                [
                    'total_movements' => count($movements),
                    'total_products' => count($costings),
                ],
                'Inventory costing projection berhasil dibangun ulang.'
            );
        } catch (DomainException $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            return Result::failure(
                $e->getMessage(),
                ['inventory_costing' => ['INVALID_INVENTORY_COSTING_PROJECTION']]
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
     * @return list<ProductInventoryCosting>
     */
    private function rebuildProjection(array $movements): array
    {
        /** @var array<string, array{qty:int,value:int}> $state */
        $state = [];

        foreach ($movements as $movement) {

            $productId = $movement->productId();

            if (!isset($state[$productId])) {
                $state[$productId] = [
                    'qty' => 0,
                    'value' => 0,
                ];
            }

            $qty = $movement->qtyDelta();

            if ($movement->movementType() === 'stock_in') {

                $state[$productId]['qty'] += $qty;
                $state[$productId]['value'] += $movement->totalCostRupiah()->amount();

                continue;
            }

            if ($movement->movementType() === 'stock_out') {

                if ($state[$productId]['qty'] <= 0) {
                    continue;
                }

                $avgCost = intdiv(
                    $state[$productId]['value'],
                    $state[$productId]['qty']
                );

                $issueQty = abs($qty);

                $state[$productId]['qty'] -= $issueQty;
                $state[$productId]['value'] -= ($avgCost * $issueQty);

                if ($state[$productId]['value'] < 0) {
                    $state[$productId]['value'] = 0;
                }
            }
        }

        ksort($state);

        $result = [];

        foreach ($state as $productId => $summary) {

            if ($summary['qty'] <= 0) {
                continue;
            }

            $inventoryValue = Money::fromInt($summary['value']);

            $avgCost = Money::fromInt(
                intdiv($summary['value'], $summary['qty'])
            );

            $result[] = ProductInventoryCosting::create(
                $productId,
                $avgCost,
                $inventoryValue,
            );
        }

        return $result;
    }
}
