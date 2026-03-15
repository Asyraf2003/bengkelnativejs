<?php

declare(strict_types=1);

namespace App\Application\Inventory\UseCases;

use App\Application\Inventory\Services\InventoryCostingProjectionBuilder;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use App\Ports\Out\Inventory\ProductInventoryCostingProjectionWriterPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RebuildInventoryCostingProjectionHandler
{
    public function __construct(
        private readonly InventoryMovementReaderPort $movements,
        private readonly ProductInventoryCostingProjectionWriterPort $projection,
        private readonly TransactionManagerPort $transactions,
        private readonly InventoryCostingProjectionBuilder $builder,
        private readonly AuditLogPort $audit
    ) {}

    public function handle(): Result
    {
        $started = false;
        try {
            $this->transactions->begin(); $started = true;

            $allMovements = $this->movements->getAll();
            $costings = $this->builder->build($allMovements);

            $this->projection->replaceAll($costings);

            $this->audit->record('inventory_costing_rebuilt', [
                'movement_count' => count($allMovements),
                'product_count' => count($costings)
            ]);

            $this->transactions->commit();
            return Result::success([
                'total_movements' => count($allMovements),
                'total_products' => count($costings)
            ], 'Inventory costing projection rebuilt.');

        } catch (DomainException $e) {
            if ($started) $this->transactions->rollBack();
            return Result::failure($e->getMessage(), ['inventory_costing' => ['REBUILD_FAILED']]);
        } catch (Throwable $e) {
            if ($started) $this->transactions->rollBack();
            throw $e;
        }
    }
}
