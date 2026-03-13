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

final class AddExternalPurchaseWorkItemFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_external_purchase_work_item_handler_stores_lines_and_updates_note_total_without_inventory_movement(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 0,
        ]);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
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

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $expectedSubtotal = 70000 + (15000 * 2) + (10000 * 1);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => $expectedSubtotal,
        ]);

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
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

        $this->assertDatabaseHas('work_item_external_purchase_lines', [
            'work_item_id' => (string) $workItem->id,
            'cost_description' => 'Busi beli luar',
            'unit_cost_rupiah' => 15000,
            'qty' => 2,
            'line_total_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('work_item_external_purchase_lines', [
            'work_item_id' => (string) $workItem->id,
            'cost_description' => 'Kabel gas beli luar',
            'unit_cost_rupiah' => 10000,
            'qty' => 1,
            'line_total_rupiah' => 10000,
        ]);

        $this->assertDatabaseCount('work_item_external_purchase_lines', 2);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
        $this->assertDatabaseCount('product_inventory_costing', 0);
    }

    public function test_add_external_purchase_work_item_handler_rejects_when_external_purchase_lines_are_empty(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 0,
        ]);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
            [
                'service_name' => 'Servis Mesin',
                'service_price_rupiah' => 70000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
            [],
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 0,
        ]);

        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('work_item_external_purchase_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }
}
