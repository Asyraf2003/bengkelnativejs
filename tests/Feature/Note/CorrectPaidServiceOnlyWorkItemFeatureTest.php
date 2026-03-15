<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CorrectPaidServiceOnlyWorkItemHandler;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CorrectPaidServiceOnlyWorkItemFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_paid_service_only_work_item_handler_updates_service_detail_total_and_refund_requirement(): void
    {
        $this->loginAsKasir();
        $this->seedPaidServiceOnlyNote();

        $handler = app(CorrectPaidServiceOnlyWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            'Servis Karburator Revisi',
            30000,
            ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            'Salah input harga jasa.',
            'owner-1',
        );

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $this->assertSame(30000, $data['note']['total_rupiah']);
        $this->assertSame(30000, $data['work_item']['subtotal_rupiah']);
        $this->assertSame(20000, $data['refund_required_rupiah']);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'work-item-1',
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'work-item-1',
            'service_name' => 'Servis Karburator Revisi',
            'service_price_rupiah' => 30000,
            'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'paid_service_only_work_item_corrected',
        ]);

        $audit = DB::table('audit_logs')
            ->where('event', 'paid_service_only_work_item_corrected')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);

        $context = json_decode((string) $audit->context, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('owner-1', $context['performed_by_actor_id']);
        $this->assertSame('note-1', $context['note_id']);
        $this->assertSame(1, $context['line_no']);
        $this->assertSame('Salah input harga jasa.', $context['reason']);
        $this->assertSame(20000, $context['refund_required_rupiah']);
        $this->assertSame(50000, $context['before']['note']['total_rupiah']);
        $this->assertSame(30000, $context['after']['note']['total_rupiah']);
        $this->assertSame('Servis Karburator', $context['before']['work_items'][0]['service_name'] ?? 'Servis Karburator');
    }

    public function test_correct_paid_service_only_work_item_handler_rejects_blank_reason(): void
    {
        $this->loginAsKasir();
        $this->seedPaidServiceOnlyNote();

        $handler = app(CorrectPaidServiceOnlyWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            'Servis Karburator Revisi',
            30000,
            ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            '   ',
            'owner-1',
        );

        $this->assertTrue($result->isFailure());
        $this->assertSame(['correction' => ['AUDIT_REASON_REQUIRED']], $result->errors());

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'work-item-1',
            'service_name' => 'Servis Karburator',
            'service_price_rupiah' => 50000,
        ]);
    }

    public function test_correct_paid_service_only_work_item_handler_rejects_unpaid_note(): void
    {
        $this->loginAsKasir();
        $this->seedUnpaidServiceOnlyNote();

        $handler = app(CorrectPaidServiceOnlyWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            'Servis Karburator Revisi',
            30000,
            ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            'Salah input harga jasa.',
            'owner-1',
        );

        $this->assertTrue($result->isFailure());
        $this->assertSame(['work_item' => ['INVALID_WORK_ITEM_STATE']], $result->errors());

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 50000,
        ]);
    }

    public function test_correct_paid_service_only_work_item_handler_rejects_non_service_only_work_item(): void
    {
        $this->loginAsKasir();
        $this->seedPaidStoreStockOnlyNote();

        $handler = app(CorrectPaidServiceOnlyWorkItemHandler::class);

        $result = $handler->handle(
            'note-1',
            1,
            'Servis Karburator Revisi',
            30000,
            ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            'Tidak boleh untuk tipe lain.',
            'owner-1',
        );

        $this->assertTrue($result->isFailure());
        $this->assertSame(['work_item' => ['INVALID_WORK_ITEM_STATE']], $result->errors());
    }

    private function seedPaidServiceOnlyNote(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 50000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-1',
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'work-item-1',
            'service_name' => 'Servis Karburator',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'payment-1',
            'amount_rupiah' => 50000,
            'paid_at' => '2026-03-15',
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'allocation-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'amount_rupiah' => 50000,
        ]);
    }

    private function seedUnpaidServiceOnlyNote(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 50000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-1',
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'work-item-1',
            'service_name' => 'Servis Karburator',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
        ]);
    }

    private function seedPaidStoreStockOnlyNote(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-1',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => '2026-03-14',
            'total_rupiah' => 40000,
        ]);

        DB::table('work_items')->insert([
            'id' => 'work-item-1',
            'note_id' => 'note-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 40000,
        ]);

        DB::table('work_item_store_stock_lines')->insert([
            'id' => 'store-line-1',
            'work_item_id' => 'work-item-1',
            'product_id' => 'product-1',
            'qty' => 1,
            'line_total_rupiah' => 40000,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'payment-1',
            'amount_rupiah' => 40000,
            'paid_at' => '2026-03-15',
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'allocation-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'amount_rupiah' => 40000,
        ]);
    }
}
