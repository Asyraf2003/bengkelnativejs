<?php

declare(strict_types=1);

namespace App\Application\Inventory\Services;

use App\Core\Inventory\Costing\ProductInventoryCosting;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Inventory\Policies\NegativeStockPolicy;
use App\Core\Inventory\ProductInventory\ProductInventory;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Inventory\{InventoryMovementWriterPort, ProductInventoryCostingReaderPort, ProductInventoryCostingWriterPort, ProductInventoryReaderPort, ProductInventoryWriterPort};
use App\Ports\Out\UuidPort;
use DateTimeImmutable;

final class IssueInventoryOperation
{
    public function __construct(
        private readonly ProductInventoryReaderPort $productInventories,
        private readonly ProductInventoryWriterPort $inventoryWriter,
        private readonly ProductInventoryCostingReaderPort $costingReader,
        private readonly ProductInventoryCostingWriterPort $costingWriter,
        private readonly InventoryMovementWriterPort $movements,
        private readonly NegativeStockPolicy $negativeStockPolicy,
        private readonly UuidPort $uuid,
    ) {}

    /** @return array{movement: InventoryMovement, product_inventory: ProductInventory, product_inventory_costing: ProductInventoryCosting} */
    public function execute(string $pId, int $qty, DateTimeImmutable $date, string $sType, string $sId): array
    {
        $pId = trim($pId); $sType = trim($sType); $sId = trim($sId);
        if ($pId === '' || $sType === '' || $sId === '') throw new DomainException('Input inventory issue wajib lengkap.');
        if ($qty <= 0) throw new DomainException('Qty issue inventory harus > 0.');

        $inv = $this->productInventories->getByProductId($pId) ?? ProductInventory::create($pId, 0);
        $this->negativeStockPolicy->assertCanIssue($inv->qtyOnHand(), $qty);

        $costing = $this->costingReader->getByProductId($pId) ?? throw new DomainException('Costing tidak ditemukan.');

        $movement = InventoryMovement::create(
            $this->uuid->generate(), $pId, 'stock_out', $sType, $sId, $date, -$qty, $costing->avgCostRupiah()
        );

        $inv->decrease($qty);
        $costing->applyOutgoingStock($inv->qtyOnHand() + $qty, $qty);

        $this->movements->createMany([$movement]);
        $this->inventoryWriter->upsert($inv);
        $this->costingWriter->upsert($costing);

        return ['movement' => $movement, 'product_inventory' => $inv, 'product_inventory_costing' => $costing];
    }
}
