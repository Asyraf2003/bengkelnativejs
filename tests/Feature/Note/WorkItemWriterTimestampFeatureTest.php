<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\WorkItemWriterPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class WorkItemWriterTimestampFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_persists_system_timestamps_on_created_work_item_group_rows(): void
    {
        $this->seedNote('note-0011');
        $this->seedProduct('product-0011');

        Carbon::setTestNow(Carbon::parse('2026-05-15 10:00:00'));

        $writer = app(WorkItemWriterPort::class);

        $writer->create(WorkItem::createServiceWithExternalPurchase(
            'wi-ext-0011',
            'note-0011',
            1,
            ServiceDetail::create('Servis External', Money::fromInt(5000), ServiceDetail::PART_SOURCE_NONE),
            [
                ExternalPurchaseLine::create('ext-0011', 'Beli luar', Money::fromInt(2000), 1),
            ],
        ));

        $writer->create(WorkItem::createServiceWithStoreStockPart(
            'wi-stock-0011',
            'note-0011',
            2,
            ServiceDetail::create('Servis Stock', Money::fromInt(7000), ServiceDetail::PART_SOURCE_NONE),
            [
                StoreStockLine::create('sto-0011', 'product-0011', 1, Money::fromInt(3000)),
            ],
        ));

        $this->assertRowHasTimestamps('work_items', 'id', 'wi-ext-0011');
        $this->assertRowHasTimestamps('work_items', 'id', 'wi-stock-0011');
        $this->assertRowHasTimestamps('work_item_service_details', 'work_item_id', 'wi-ext-0011');
        $this->assertRowHasTimestamps('work_item_service_details', 'work_item_id', 'wi-stock-0011');
        $this->assertRowHasTimestamps('work_item_external_purchase_lines', 'id', 'ext-0011');
        $this->assertRowHasTimestamps('work_item_store_stock_lines', 'id', 'sto-0011');
    }

    public function test_update_status_preserves_created_at_and_updates_updated_at(): void
    {
        $this->seedNote('note-status-0011');

        Carbon::setTestNow(Carbon::parse('2026-05-15 10:00:00'));

        $writer = app(WorkItemWriterPort::class);

        $writer->create(WorkItem::createServiceOnly(
            'wi-status-0011',
            'note-status-0011',
            1,
            ServiceDetail::create('Servis Lama', Money::fromInt(5000), ServiceDetail::PART_SOURCE_NONE),
        ));

        $before = DB::table('work_items')->where('id', 'wi-status-0011')->first();

        Carbon::setTestNow(Carbon::parse('2026-05-15 10:05:00'));

        $writer->updateStatus(WorkItem::createServiceOnly(
            'wi-status-0011',
            'note-status-0011',
            1,
            ServiceDetail::create('Servis Lama', Money::fromInt(5000), ServiceDetail::PART_SOURCE_NONE),
            WorkItem::STATUS_DONE,
        ));

        $after = DB::table('work_items')->where('id', 'wi-status-0011')->first();

        $this->assertSame(WorkItem::STATUS_DONE, $after->status);
        $this->assertSame((string) $before->created_at, (string) $after->created_at);
        $this->assertNotSame((string) $before->updated_at, (string) $after->updated_at);
    }

    public function test_update_service_only_preserves_created_at_and_updates_parent_and_detail_updated_at(): void
    {
        $this->seedNote('note-service-0011');

        Carbon::setTestNow(Carbon::parse('2026-05-15 10:00:00'));

        $writer = app(WorkItemWriterPort::class);

        $writer->create(WorkItem::createServiceOnly(
            'wi-service-0011',
            'note-service-0011',
            1,
            ServiceDetail::create('Servis Lama', Money::fromInt(5000), ServiceDetail::PART_SOURCE_NONE),
        ));

        $beforeParent = DB::table('work_items')->where('id', 'wi-service-0011')->first();
        $beforeDetail = DB::table('work_item_service_details')->where('work_item_id', 'wi-service-0011')->first();

        Carbon::setTestNow(Carbon::parse('2026-05-15 10:05:00'));

        $writer->updateServiceOnly(WorkItem::createServiceOnly(
            'wi-service-0011',
            'note-service-0011',
            1,
            ServiceDetail::create('Servis Baru', Money::fromInt(6000), ServiceDetail::PART_SOURCE_NONE),
        ));

        $afterParent = DB::table('work_items')->where('id', 'wi-service-0011')->first();
        $afterDetail = DB::table('work_item_service_details')->where('work_item_id', 'wi-service-0011')->first();

        $this->assertSame(6000, (int) $afterParent->subtotal_rupiah);
        $this->assertSame('Servis Baru', $afterDetail->service_name);
        $this->assertSame(6000, (int) $afterDetail->service_price_rupiah);

        $this->assertSame((string) $beforeParent->created_at, (string) $afterParent->created_at);
        $this->assertNotSame((string) $beforeParent->updated_at, (string) $afterParent->updated_at);

        $this->assertSame((string) $beforeDetail->created_at, (string) $afterDetail->created_at);
        $this->assertNotSame((string) $beforeDetail->updated_at, (string) $afterDetail->updated_at);
    }

    private function seedNote(string $id): void
    {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => 'Budi',
            'transaction_date' => '2026-05-15',
            'total_rupiah' => 0,
        ]);
    }

    private function seedProduct(string $id): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => 'KB-' . $id,
            'nama_barang' => 'Oli Mesin',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 15000,
        ]);
    }

    private function assertRowHasTimestamps(string $table, string $keyColumn, string $keyValue): void
    {
        $row = DB::table($table)
            ->where($keyColumn, $keyValue)
            ->select([$keyColumn, 'created_at', 'updated_at'])
            ->first();

        $this->assertNotNull($row, "Missing row {$table}.{$keyColumn}={$keyValue}");
        $this->assertNotNull($row->created_at, "Missing {$table}.created_at");
        $this->assertNotNull($row->updated_at, "Missing {$table}.updated_at");
    }
}
