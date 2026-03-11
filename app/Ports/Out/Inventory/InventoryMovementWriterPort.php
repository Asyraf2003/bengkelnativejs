<?php

declare(strict_types=1);

namespace App\Ports\Out\Inventory;

use App\Core\Inventory\Movement\InventoryMovement;

interface InventoryMovementWriterPort
{
    /**
     * @param list<InventoryMovement> $movements
     */
    public function createMany(array $movements): void;
}
