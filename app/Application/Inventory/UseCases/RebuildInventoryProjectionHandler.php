<?php

declare(strict_types=1);

namespace App\Application\Inventory\UseCases;

use App\Application\Inventory\Services\InventoryProjectionBuilder;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Inventory\{InventoryMovementReaderPort, ProductInventoryProjectionWriterPort};
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RebuildInventoryProjectionHandler
{
    public function __construct(
        private readonly InventoryMovementReaderPort $movements,
        private readonly ProductInventoryProjectionWriterPort $projection,
        private readonly TransactionManagerPort $transactions,
        private readonly InventoryProjectionBuilder $builder,
        private readonly AuditLogPort $audit
    ) {}

    public function handle(): Result
    {
        $started = false;
        try {
            $this->transactions->begin(); $started = true;

            $allMovements = $this->movements->getAll();
            $inventories = $this->builder->build($allMovements);

            $this->projection->replaceAll($inventories);

            $this->audit->record('inventory_projection_rebuilt', [
                'movement_count' => count($allMovements),
                'product_count' => count($inventories)
            ]);

            $this->transactions->commit();
            return Result::success([
                'total_movements' => count($allMovements),
                'total_products' => count($inventories)
            ], 'Inventory projection rebuilt successfully.');

        } catch (DomainException $e) {
            if ($started) $this->transactions->rollBack();
            return Result::failure($e->getMessage(), ['inventory' => ['REBUILD_FAILED']]);
        } catch (Throwable $e) {
            if ($started) $this->transactions->rollBack();
            throw $e;
        }
    }
}
