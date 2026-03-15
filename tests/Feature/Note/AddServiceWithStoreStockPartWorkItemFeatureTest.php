<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\AddWorkItemHandler;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AddServiceWithStoreStockPartWorkItemFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_service_with_store_stock_part_handler_stores_service_and_stock_lines_updates_note_and_issues_inventory(): void
    {
        $this->loginAsKasir();
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 0);
        $this->seedProduct('product-1', 'KB-001', 'Oli Mesin', 'Federal', null, 15000);
        $this->seedInventory('product-1', 5);
        $this->seedInventoryCosting('product-1', 10000, 50000);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            [
                'service_name' => 'Servis Mesin',
                'service_price_rupiah' => 70000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
            [],
            [
                [
                    'product_id' => 'product-1',
                    'qty' => 2,
                    'line_total_rupiah' => 40000,
                ],
            ],
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $expectedSubtotal = 70000 + 40000;

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => $expectedSubtotal,
        ]);

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => $expectedSubtotal,
        ]);

        $workItem = DB::table('work_items')
            ->where('note_id', 'note-1')
            ->where('line_no', 1)
            ->first();

        $this->assertNotNull($workItem);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => (string) $workItem->id,
            'service_name' => 'Servis Mesin',
            'service_price_rupiah' => 70000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => (string) $workItem->id,
            'product_id' => 'product-1',
            'qty' => 2,
            'line_total_rupiah' => 40000,
        ]);

        $storeStockLine = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', (string) $workItem->id)
            ->where('product_id', 'product-1')
            ->first();

        $this->assertNotNull($storeStockLine);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $storeStockLine->id,
            'tanggal_mutasi' => '2026-03-14',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -20000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 3,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 30000,
        ]);

        $this->assertDatabaseCount('work_item_external_purchase_lines', 0);
    }

    public function test_add_service_with_store_stock_part_handler_rejects_when_stock_is_insufficient(): void
    {
        $this->loginAsKasir();
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 0);
        $this->seedProduct('product-1', 'KB-001', 'Oli Mesin', 'Federal', null, 15000);
        $this->seedInventory('product-1', 1);
        $this->seedInventoryCosting('product-1', 10000, 10000);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            [
                'service_name' => 'Servis Mesin',
                'service_price_rupiah' => 70000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
            [],
            [
                [
                    'product_id' => 'product-1',
                    'qty' => 2,
                    'line_total_rupiah' => 40000,
                ],
            ],
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['inventory' => ['INVENTORY_INSUFFICIENT_STOCK']],
            $result->errors(),
        );

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 0,
        ]);

        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 1,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 10000,
        ]);
    }

    public function test_add_service_with_store_stock_part_handler_rejects_when_line_total_is_below_floor_pricing(): void
    {
        $this->loginAsKasir();
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 0);
        $this->seedProduct('product-1', 'KB-001', 'Oli Mesin', 'Federal', null, 15000);
        $this->seedInventory('product-1', 5);
        $this->seedInventoryCosting('product-1', 10000, 50000);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            [
                'service_name' => 'Servis Mesin',
                'service_price_rupiah' => 70000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
            [],
            [
                [
                    'product_id' => 'product-1',
                    'qty' => 2,
                    'line_total_rupiah' => 20000,
                ],
            ],
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['pricing' => ['PRICING_BELOW_MINIMUM_SELLING_PRICE']],
            $result->errors(),
        );

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 0,
        ]);

        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 5,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 50000,
        ]);
    }

    private function seedNote(
        string $id,
        string $customerName,
        string $transactionDate,
        int $totalRupiah,
    ): void {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => $customerName,
            'transaction_date' => $transactionDate,
            'total_rupiah' => $totalRupiah,
        ]);
    }

    private function seedProduct(
        string $id,
        ?string $kodeBarang,
        string $namaBarang,
        string $merek,
        ?int $ukuran,
        int $hargaJual,
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => $kodeBarang,
            'nama_barang' => $namaBarang,
            'merek' => $merek,
            'ukuran' => $ukuran,
            'harga_jual' => $hargaJual,
        ]);
    }

    private function seedInventory(string $productId, int $qtyOnHand): void
    {
        DB::table('product_inventory')->insert([
            'product_id' => $productId,
            'qty_on_hand' => $qtyOnHand,
        ]);
    }

    private function seedInventoryCosting(
        string $productId,
        int $avgCostRupiah,
        int $inventoryValueRupiah,
    ): void {
        DB::table('product_inventory_costing')->insert([
            'product_id' => $productId,
            'avg_cost_rupiah' => $avgCostRupiah,
            'inventory_value_rupiah' => $inventoryValueRupiah,
        ]);
    }
}
