<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Application\Inventory\UseCases\RebuildInventoryCostingProjectionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RebuildInventoryCostingProjectionWithStockOutFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuild_costing_projection_handles_stock_out(): void
    {
        DB::table('inventory_movements')->insert([
            [
                'id' => 'm1',
                'product_id' => 'product-1',
                'movement_type' => 'stock_in',
                'source_type' => 'receipt',
                'source_id' => 'r1',
                'tanggal_mutasi' => '2026-03-01',
                'qty_delta' => 10,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => 100000,
            ],
            [
                'id' => 'm2',
                'product_id' => 'product-1',
                'movement_type' => 'stock_out',
                'source_type' => 'note',
                'source_id' => 'n1',
                'tanggal_mutasi' => '2026-03-02',
                'qty_delta' => -4,
                'unit_cost_rupiah' => 10000,
                'total_cost_rupiah' => -40000,
            ],
        ]);

        $handler = app(RebuildInventoryCostingProjectionHandler::class);

        $handler->handle();

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 60000,
        ]);
    }
}
