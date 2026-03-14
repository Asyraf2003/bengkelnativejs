<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\AddWorkItemHandler;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\Note\NoteReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReadNoteMultiItemFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_reader_can_rehydrate_multi_item_note_with_mixed_work_item_types_and_consistent_total(): void
    {
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 0);
        $this->seedProduct('product-1', 'KB-001', 'Oli Mesin', 'Federal', null, 15000);
        $this->seedInventory('product-1', 10);
        $this->seedInventoryCosting('product-1', 10000, 100000);

        $handler = app(AddWorkItemHandler::class);

        $result1 = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Karburator',
                'service_price_rupiah' => 50000,
                'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            ],
        );

        $this->assertInstanceOf(Result::class, $result1);
        $this->assertTrue($result1->isSuccess());

        $result2 = $handler->handle(
            'note-1',
            2,
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
            [
                'service_name' => 'Servis Mesin',
                'service_price_rupiah' => 70000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
            [
                [
                    'cost_description' => 'Busi beli luar',
                    'unit_cost_rupiah' => 15000,
                    'qty' => 2,
                ],
                [
                    'cost_description' => 'Kabel gas beli luar',
                    'unit_cost_rupiah' => 10000,
                    'qty' => 1,
                ],
            ],
        );

        $this->assertInstanceOf(Result::class, $result2);
        $this->assertTrue($result2->isSuccess());

        $result3 = $handler->handle(
            'note-1',
            3,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            [],
            [],
            [
                [
                    'product_id' => 'product-1',
                    'qty' => 2,
                    'line_total_rupiah' => 40000,
                ],
            ],
        );

        $this->assertInstanceOf(Result::class, $result3);
        $this->assertTrue($result3->isSuccess());

        $result4 = $handler->handle(
            'note-1',
            4,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            [
                'service_name' => 'Servis Tune Up',
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

        $this->assertInstanceOf(Result::class, $result4);
        $this->assertTrue($result4->isSuccess());

        $expectedTotal = 50000 + 110000 + 40000 + 110000;

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => $expectedTotal,
        ]);

        $this->assertDatabaseCount('work_items', 4);
        $this->assertDatabaseCount('work_item_service_details', 3);
        $this->assertDatabaseCount('work_item_external_purchase_lines', 2);
        $this->assertDatabaseCount('work_item_store_stock_lines', 2);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 6,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 60000,
        ]);

        $noteReader = app(NoteReaderPort::class);
        $note = $noteReader->getById('note-1');

        $this->assertNotNull($note);
        $this->assertSame('note-1', $note->id());
        $this->assertSame('Budi Santoso', $note->customerName());
        $this->assertSame('2026-03-14', $note->transactionDate()->format('Y-m-d'));
        $this->assertSame($expectedTotal, $note->totalRupiah()->amount());

        $workItems = $note->workItems();

        $this->assertCount(4, $workItems);

        $this->assertSame(1, $workItems[0]->lineNo());
        $this->assertSame(WorkItem::TYPE_SERVICE_ONLY, $workItems[0]->transactionType());
        $this->assertSame(WorkItem::STATUS_OPEN, $workItems[0]->status());
        $this->assertSame(50000, $workItems[0]->subtotalRupiah()->amount());
        $this->assertNotNull($workItems[0]->serviceDetail());
        $this->assertSame(
            ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            $workItems[0]->serviceDetail()?->partSource(),
        );
        $this->assertCount(0, $workItems[0]->externalPurchaseLines());
        $this->assertCount(0, $workItems[0]->storeStockLines());

        $this->assertSame(2, $workItems[1]->lineNo());
        $this->assertSame(WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, $workItems[1]->transactionType());
        $this->assertSame(WorkItem::STATUS_OPEN, $workItems[1]->status());
        $this->assertSame(110000, $workItems[1]->subtotalRupiah()->amount());
        $this->assertNotNull($workItems[1]->serviceDetail());
        $this->assertSame(ServiceDetail::PART_SOURCE_NONE, $workItems[1]->serviceDetail()?->partSource());
        $this->assertCount(2, $workItems[1]->externalPurchaseLines());
        $this->assertCount(0, $workItems[1]->storeStockLines());

        $this->assertSame(3, $workItems[2]->lineNo());
        $this->assertSame(WorkItem::TYPE_STORE_STOCK_SALE_ONLY, $workItems[2]->transactionType());
        $this->assertSame(WorkItem::STATUS_OPEN, $workItems[2]->status());
        $this->assertSame(40000, $workItems[2]->subtotalRupiah()->amount());
        $this->assertNull($workItems[2]->serviceDetail());
        $this->assertCount(0, $workItems[2]->externalPurchaseLines());
        $this->assertCount(1, $workItems[2]->storeStockLines());
        $this->assertSame('product-1', $workItems[2]->storeStockLines()[0]->productId());
        $this->assertSame(2, $workItems[2]->storeStockLines()[0]->qty());
        $this->assertSame(40000, $workItems[2]->storeStockLines()[0]->lineTotalRupiah()->amount());

        $this->assertSame(4, $workItems[3]->lineNo());
        $this->assertSame(WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, $workItems[3]->transactionType());
        $this->assertSame(WorkItem::STATUS_OPEN, $workItems[3]->status());
        $this->assertSame(110000, $workItems[3]->subtotalRupiah()->amount());
        $this->assertNotNull($workItems[3]->serviceDetail());
        $this->assertSame(ServiceDetail::PART_SOURCE_NONE, $workItems[3]->serviceDetail()?->partSource());
        $this->assertCount(0, $workItems[3]->externalPurchaseLines());
        $this->assertCount(1, $workItems[3]->storeStockLines());
        $this->assertSame('product-1', $workItems[3]->storeStockLines()[0]->productId());
        $this->assertSame(2, $workItems[3]->storeStockLines()[0]->qty());
        $this->assertSame(40000, $workItems[3]->storeStockLines()[0]->lineTotalRupiah()->amount());
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
