<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Application\Inventory\UseCases\RebuildInventoryProjectionHandler;
use App\Application\Shared\DTO\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RebuildInventoryProjectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuild_inventory_projection_handler_replaces_stale_projection_from_official_ledger(): void
    {
        $this->seedInventoryMovement(
            'movement-1',
            'product-1',
            'stock_in',
            'supplier_receipt_line',
            'receipt-line-1',
            '2026-03-13',
            5,
            10000
        );

        $this->seedInventoryMovement(
            'movement-2',
            'product-1',
            'stock_in',
            'supplier_receipt_line',
            'receipt-line-2',
            '2026-03-14',
            2,
            12000
        );

        $this->seedInventoryMovement(
            'movement-3',
            'product-2',
            'stock_in',
            'supplier_receipt_line',
            'receipt-line-3',
            '2026-03-13',
            3,
            15000
        );

        DB::table('product_inventory')->insert([
            [
                'product_id' => 'product-1',
                'qty_on_hand' => 99,
            ],
            [
                'product_id' => 'product-legacy',
                'qty_on_hand' => 44,
            ],
        ]);

        $handler = app(RebuildInventoryProjectionHandler::class);

        $result = $handler->handle();

        $this->assertInstanceOf(Result::class, $result);

        $this->assertDatabaseCount('inventory_movements', 3);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-2',
            'qty_on_hand' => 3,
        ]);

        $this->assertDatabaseMissing('product_inventory', [
            'product_id' => 'product-legacy',
        ]);

        $this->assertDatabaseCount('product_inventory', 2);
    }

    public function test_rebuild_inventory_projection_handler_clears_stale_projection_when_ledger_is_empty(): void
    {
        DB::table('product_inventory')->insert([
            [
                'product_id' => 'product-1',
                'qty_on_hand' => 10,
            ],
            [
                'product_id' => 'product-2',
                'qty_on_hand' => 5,
            ],
        ]);

        $handler = app(RebuildInventoryProjectionHandler::class);

        $result = $handler->handle();

        $this->assertInstanceOf(Result::class, $result);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
    }

    private function seedInventoryMovement(
        string $id,
        string $productId,
        string $movementType,
        string $sourceType,
        string $sourceId,
        string $tanggalMutasi,
        int $qtyDelta,
        int $unitCostRupiah,
    ): void {
        DB::table('inventory_movements')->insert([
            'id' => $id,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'tanggal_mutasi' => $tanggalMutasi,
            'qty_delta' => $qtyDelta,
            'unit_cost_rupiah' => $unitCostRupiah,
            'total_cost_rupiah' => $qtyDelta * $unitCostRupiah,
        ]);
    }
}
