<?php

declare(strict_types=1);

namespace App\Application\Inventory\Services;

use App\Core\Inventory\Costing\ProductInventoryCosting;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Inventory\Policies\NegativeStockPolicy;
use App\Core\Inventory\ProductInventory\ProductInventory;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Inventory\InventoryMovementWriterPort;
use App\Ports\Out\Inventory\ProductInventoryCostingReaderPort;
use App\Ports\Out\Inventory\ProductInventoryCostingWriterPort;
use App\Ports\Out\Inventory\ProductInventoryReaderPort;
use App\Ports\Out\Inventory\ProductInventoryWriterPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;

final class IssueInventoryOperation
{
    public function __construct(
        private readonly ProductInventoryReaderPort $productInventories,
        private readonly ProductInventoryWriterPort $productInventoryWriter,
        private readonly ProductInventoryCostingReaderPort $productInventoryCostings,
        private readonly ProductInventoryCostingWriterPort $productInventoryCostingWriter,
        private readonly InventoryMovementWriterPort $inventoryMovements,
        private readonly NegativeStockPolicy $negativeStockPolicy,
        private readonly UuidPort $uuid,
    ) {
    }

    /**
     * @return array{
     *     movement: InventoryMovement,
     *     product_inventory: ProductInventory,
     *     product_inventory_costing: ProductInventoryCosting
     * }
     */
    public function execute(
        string $productId,
        int $qtyIssue,
        DateTimeImmutable $tanggalMutasi,
        string $sourceType,
        string $sourceId,
    ): array {
        $normalizedProductId = $this->normalizeRequired($productId, 'Product id pada inventory issue wajib ada.');
        $normalizedSourceType = $this->normalizeRequired($sourceType, 'Source type pada inventory issue wajib ada.');
        $normalizedSourceId = $this->normalizeRequired($sourceId, 'Source id pada inventory issue wajib ada.');

        if ($qtyIssue <= 0) {
            throw new DomainException('Qty issue inventory harus lebih besar dari nol.');
        }

        $inventory = $this->productInventories->getByProductId($normalizedProductId)
            ?? ProductInventory::create($normalizedProductId, 0);

        $availableQty = $inventory->qtyOnHand();

        $this->negativeStockPolicy->assertCanIssue($availableQty, $qtyIssue);

        $costing = $this->productInventoryCostings->getByProductId($normalizedProductId);

        if ($costing === null) {
            throw new DomainException('Inventory costing projection tidak ditemukan untuk product ini.');
        }

        $movement = InventoryMovement::create(
            $this->uuid->generate(),
            $normalizedProductId,
            'stock_out',
            $normalizedSourceType,
            $normalizedSourceId,
            $tanggalMutasi,
            -$qtyIssue,
            $costing->avgCostRupiah(),
        );

        $inventory->decrease($qtyIssue);
        $costing->applyOutgoingStock($availableQty, $qtyIssue);

        $this->inventoryMovements->createMany([$movement]);
        $this->productInventoryWriter->upsert($inventory);
        $this->productInventoryCostingWriter->upsert($costing);

        return [
            'movement' => $movement,
            'product_inventory' => $inventory,
            'product_inventory_costing' => $costing,
        ];
    }

    private function normalizeRequired(string $value, string $message): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new DomainException($message);
        }

        return $normalized;
    }
}
