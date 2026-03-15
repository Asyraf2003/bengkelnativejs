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

final class AddWorkItemToPaidNoteFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_work_item_handler_rejects_new_item_when_note_is_fully_paid(): void
    {
        $this->loginAsKasir();
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 50000);
        $this->seedCustomerPayment('payment-1', 50000, '2026-03-15');
        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 50000);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Karburator',
                'service_price_rupiah' => 30000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertSame(
            ['note' => ['NOTE_NEW_ITEMS_NOT_ALLOWED_AFTER_PAID']],
            $result->errors(),
        );

        $this->assertDatabaseCount('work_items', 0);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 50000,
        ]);
    }

    public function test_add_work_item_handler_allows_new_item_for_zero_total_note(): void
    {
        $this->loginAsKasir();
        $this->seedNote('note-1', 'Budi Santoso', '2026-03-14', 0);

        $handler = app(AddWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            [
                'service_name' => 'Servis Karburator',
                'service_price_rupiah' => 30000,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ],
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $this->assertDatabaseCount('work_items', 1);

        $this->assertDatabaseHas('work_items', [
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 30000,
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

    private function seedCustomerPayment(
        string $id,
        int $amountRupiah,
        string $paidAt,
    ): void {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ]);
    }

    private function seedPaymentAllocation(
        string $id,
        string $customerPaymentId,
        string $noteId,
        int $amountRupiah,
    ): void {
        DB::table('payment_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $customerPaymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
        ]);
    }
}
