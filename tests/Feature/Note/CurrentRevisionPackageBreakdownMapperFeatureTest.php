<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\CurrentRevision\CurrentRevisionPackageBreakdownMapper;
use App\Core\Note\Revision\NoteRevisionLineSnapshot;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CurrentRevisionPackageBreakdownMapperFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prefers_historical_product_name_snapshot_over_current_product_name(): void
    {
        $this->insertProduct('product-1', 'Nama Current Berubah');

        $line = $this->packageLine([
            'id' => 'sto-1',
            'product_id' => 'product-1',
            'product_name_snapshot' => 'Nama Snapshot Lama',
            'qty' => 2,
            'line_total_rupiah' => 100000,
        ]);

        $mapped = (new CurrentRevisionPackageBreakdownMapper())->map($line, $line->payload());

        self::assertIsArray($mapped);
        self::assertSame('Nama Snapshot Lama', $mapped['parts'][0]['product_name']);
        self::assertNotSame('Nama Current Berubah', $mapped['parts'][0]['product_name']);
    }

    public function test_it_falls_back_to_current_product_name_for_legacy_payload_without_snapshot(): void
    {
        $this->insertProduct('product-legacy', 'Nama Current Legacy');

        $line = $this->packageLine([
            'id' => 'sto-legacy',
            'product_id' => 'product-legacy',
            'qty' => 1,
            'line_total_rupiah' => 50000,
        ]);

        $mapped = (new CurrentRevisionPackageBreakdownMapper())->map($line, $line->payload());

        self::assertIsArray($mapped);
        self::assertSame('Nama Current Legacy', $mapped['parts'][0]['product_name']);
    }

    /**
     * @param array<string, mixed> $storeStockLine
     */
    private function packageLine(array $storeStockLine): NoteRevisionLineSnapshot
    {
        return NoteRevisionLineSnapshot::create(
            'rev-line-1',
            'rev-1',
            'wi-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            125000,
            'Service Paket',
            25000,
            [
                'work_item_root_id' => 'wi-1',
                'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
                'status' => WorkItem::STATUS_OPEN,
                'external_purchase_lines' => [],
                'store_stock_lines' => [$storeStockLine],
                'service' => [
                    'service_name' => 'Service Paket',
                    'service_price_rupiah' => 25000,
                    'part_source' => 'store_stock',
                ],
            ],
        );
    }

    private function insertProduct(string $productId, string $name): void
    {
        DB::table('products')->insert([
            'id' => $productId,
            'kode_barang' => 'KB-' . $productId,
            'nama_barang' => $name,
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 50000,
        ]);
    }
}
