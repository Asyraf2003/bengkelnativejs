<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentRecorder;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceInlinePaymentRecorderFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_full_uses_outstanding_after_existing_legacy_allocation(): void
    {
        $today = '2026-03-15';

        DB::table('notes')->insert([
            'id' => 'note-inline-017-1',
            'customer_name' => 'Budi Existing Paid',
            'customer_phone' => '0811111111',
            'transaction_date' => $today,
            'total_rupiah' => 100000,
            'note_state' => Note::STATE_OPEN,
        ]);

        DB::table('work_items')->insert([
            'id' => 'wi-inline-017-1',
            'note_id' => 'note-inline-017-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 100000,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'wi-inline-017-1',
            'service_name' => 'Servis Existing Paid',
            'service_price_rupiah' => 100000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('customer_payments')->insert([
            'id' => 'payment-inline-017-existing-1',
            'amount_rupiah' => 40000,
            'paid_at' => $today,
        ]);

        DB::table('payment_allocations')->insert([
            'id' => 'allocation-inline-017-existing-1',
            'customer_payment_id' => 'payment-inline-017-existing-1',
            'note_id' => 'note-inline-017-1',
            'amount_rupiah' => 40000,
        ]);

        $workItem = WorkItem::createServiceOnly(
            'wi-inline-017-1',
            'note-inline-017-1',
            1,
            ServiceDetail::create(
                'Servis Existing Paid',
                Money::fromInt(100000),
                ServiceDetail::PART_SOURCE_NONE,
            ),
        );

        $note = Note::rehydrate(
            'note-inline-017-1',
            'Budi Existing Paid',
            '0811111111',
            new DateTimeImmutable($today),
            Money::fromInt(100000),
            [$workItem],
            Note::STATE_OPEN,
        );

        $summary = app(CreateTransactionWorkspaceInlinePaymentRecorder::class)->record($note, [
            'decision' => 'pay_full',
            'payment_method' => 'transfer',
            'paid_at' => $today,
            'amount_paid_rupiah' => null,
            'amount_received_rupiah' => null,
        ]);

        $newPaymentTotal = (int) DB::table('customer_payments')
            ->where('id', '<>', 'payment-inline-017-existing-1')
            ->sum('amount_rupiah');

        $legacyAllocatedTotal = (int) DB::table('payment_allocations')
            ->where('note_id', 'note-inline-017-1')
            ->sum('amount_rupiah');

        $componentAllocatedTotal = (int) DB::table('payment_component_allocations')
            ->where('note_id', 'note-inline-017-1')
            ->sum('allocated_amount_rupiah');

        $this->assertSame(60000, $summary['amount_paid_rupiah']);
        $this->assertSame(60000, $newPaymentTotal);
        $this->assertSame(40000, $legacyAllocatedTotal);
        $this->assertSame(60000, $componentAllocatedTotal);
        $this->assertSame(100000, $legacyAllocatedTotal + $componentAllocatedTotal);
    }
}
