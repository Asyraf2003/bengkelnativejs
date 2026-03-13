<?php

declare(strict_types=1);

namespace App\Application\Inventory\UseCases;

use App\Application\Inventory\Services\IssueInventoryOperation;
use App\Application\Shared\DTO\Result;
use App\Core\Inventory\Costing\ProductInventoryCosting;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Inventory\ProductInventory\ProductInventory;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\TransactionManagerPort;
use DateTimeImmutable;
use Throwable;

final class IssueInventoryHandler
{
    public function __construct(
        private readonly IssueInventoryOperation $issueInventory,
        private readonly TransactionManagerPort $transactions,
    ) {
    }

    public function handle(
        string $productId,
        int $qtyIssue,
        string $tanggalMutasi,
        string $sourceType,
        string $sourceId,
    ): Result {
        try {
            $movementDate = $this->parseTanggalMutasi($tanggalMutasi);
        } catch (DomainException $e) {
            return Result::failure(
                $e->getMessage(),
                ['inventory' => ['INVALID_INVENTORY_ISSUE']]
            );
        }

        $transactionStarted = false;

        try {
            $this->transactions->begin();
            $transactionStarted = true;

            $result = $this->issueInventory->execute(
                $productId,
                $qtyIssue,
                $movementDate,
                $sourceType,
                $sourceId,
            );

            /** @var InventoryMovement $movement */
            $movement = $result['movement'];

            /** @var ProductInventory $inventory */
            $inventory = $result['product_inventory'];

            /** @var ProductInventoryCosting $costing */
            $costing = $result['product_inventory_costing'];

            $this->transactions->commit();

            return Result::success(
                [
                    'movement' => [
                        'id' => $movement->id(),
                        'product_id' => $movement->productId(),
                        'movement_type' => $movement->movementType(),
                        'source_type' => $movement->sourceType(),
                        'source_id' => $movement->sourceId(),
                        'tanggal_mutasi' => $movement->tanggalMutasi()->format('Y-m-d'),
                        'qty_delta' => $movement->qtyDelta(),
                        'unit_cost_rupiah' => $movement->unitCostRupiah()->amount(),
                        'total_cost_rupiah' => $movement->totalCostRupiah()->amount(),
                    ],
                    'product_inventory' => [
                        'product_id' => $inventory->productId(),
                        'qty_on_hand' => $inventory->qtyOnHand(),
                    ],
                    'product_inventory_costing' => [
                        'product_id' => $costing->productId(),
                        'avg_cost_rupiah' => $costing->avgCostRupiah()->amount(),
                        'inventory_value_rupiah' => $costing->inventoryValueRupiah()->amount(),
                    ],
                ],
                'Inventory issue berhasil dibuat.'
            );
        } catch (DomainException $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            return Result::failure(
                $e->getMessage(),
                ['inventory' => ['INVALID_INVENTORY_ISSUE']]
            );
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }

    private function parseTanggalMutasi(string $tanggalMutasi): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', trim($tanggalMutasi));

        if ($parsed === false || $parsed->format('Y-m-d') !== trim($tanggalMutasi)) {
            throw new DomainException('Tanggal mutasi inventory issue wajib berupa tanggal yang valid dengan format Y-m-d.');
        }

        return $parsed;
    }
}
