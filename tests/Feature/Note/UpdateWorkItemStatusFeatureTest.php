<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\AddWorkItemHandler;
use App\Application\Note\UseCases\UpdateWorkItemStatusHandler;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\Note\NoteReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UpdateWorkItemStatusFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_work_item_status_handler_persists_mixed_statuses_in_same_note(): void
    {
        $this->loginAsKasir();
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 0);

        $addWorkItem = app(AddWorkItemHandler::class);

        $result1 = $addWorkItem->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Karburator',
                'service_price_rupiah' => 30000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
        );

        $result2 = $addWorkItem->handle(
            'note-1',
            2,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Tune Up',
                'service_price_rupiah' => 40000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
        );

        $result3 = $addWorkItem->handle(
            'note-1',
            3,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Mesin',
                'service_price_rupiah' => 50000,
                'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            ],
        );

        $this->assertInstanceOf(Result::class, $result1);
        $this->assertTrue($result1->isSuccess());
        $this->assertInstanceOf(Result::class, $result2);
        $this->assertTrue($result2->isSuccess());
        $this->assertInstanceOf(Result::class, $result3);
        $this->assertTrue($result3->isSuccess());

        $updateStatus = app(UpdateWorkItemStatusHandler::class);

        $doneResult = $updateStatus->handle(
            'note-1',
            2,
            WorkItem::STATUS_DONE,
        );

        $canceledResult = $updateStatus->handle(
            'note-1',
            3,
            WorkItem::STATUS_CANCELED,
        );

        $this->assertInstanceOf(Result::class, $doneResult);
        $this->assertTrue($doneResult->isSuccess());

        $this->assertInstanceOf(Result::class, $canceledResult);
        $this->assertTrue($canceledResult->isSuccess());

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 1,
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 2,
            'status' => WorkItem::STATUS_DONE,
        ]);

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 3,
            'status' => WorkItem::STATUS_CANCELED,
        ]);

        $noteReader = app(NoteReaderPort::class);
        $note = $noteReader->getById('note-1');

        $this->assertNotNull($note);

        $workItems = $note->workItems();

        $this->assertCount(3, $workItems);

        $this->assertSame(1, $workItems[0]->lineNo());
        $this->assertSame(WorkItem::STATUS_OPEN, $workItems[0]->status());

        $this->assertSame(2, $workItems[1]->lineNo());
        $this->assertSame(WorkItem::STATUS_DONE, $workItems[1]->status());

        $this->assertSame(3, $workItems[2]->lineNo());
        $this->assertSame(WorkItem::STATUS_CANCELED, $workItems[2]->status());
    }

    public function test_update_work_item_status_handler_rejects_when_work_item_line_is_not_found(): void
    {
        $this->loginAsKasir();
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 0);

        $addWorkItem = app(AddWorkItemHandler::class);

        $createResult = $addWorkItem->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Karburator',
                'service_price_rupiah' => 30000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
        );

        $this->assertInstanceOf(Result::class, $createResult);
        $this->assertTrue($createResult->isSuccess());

        $updateStatus = app(UpdateWorkItemStatusHandler::class);

        $result = $updateStatus->handle(
            'note-1',
            2,
            WorkItem::STATUS_DONE,
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['work_item' => ['INVALID_WORK_ITEM_STATE']],
            $result->errors(),
        );

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 1,
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseMissing('work_items', [
            'note_id' => 'note-1',
            'line_no' => 2,
        ]);
    }

    public function test_update_work_item_status_handler_rejects_unsupported_target_status(): void
    {
        $this->loginAsKasir();
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 0);

        $addWorkItem = app(AddWorkItemHandler::class);

        $createResult = $addWorkItem->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Karburator',
                'service_price_rupiah' => 30000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
        );

        $this->assertInstanceOf(Result::class, $createResult);
        $this->assertTrue($createResult->isSuccess());

        $updateStatus = app(UpdateWorkItemStatusHandler::class);

        $result = $updateStatus->handle(
            'note-1',
            1,
            'archived',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['work_item' => ['INVALID_WORK_ITEM_STATE']],
            $result->errors(),
        );

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 1,
            'status' => WorkItem::STATUS_OPEN,
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
}
