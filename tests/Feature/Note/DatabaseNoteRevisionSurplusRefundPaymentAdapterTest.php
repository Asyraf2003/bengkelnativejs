<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;
use App\Ports\Out\Note\NoteRevisionSurplusRefundDueSourceReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentWriterPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DatabaseNoteRevisionSurplusRefundPaymentAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_writer_persists_and_reader_finds_active_refund_payment_by_idempotency(): void
    {
        $this->seedRefundDueDisposition();
        $this->seedAuditEvent(
            'audit-event-payment-test-001',
            'note_revision_surplus_refund_payment',
            'surplus-refund-payment-test-001',
            'note_revision_surplus_refund_paid_recorded',
        );

        $writer = $this->app->make(NoteRevisionSurplusRefundPaymentWriterPort::class);
        $reader = $this->app->make(NoteRevisionSurplusRefundPaymentReaderPort::class);

        $writer->create($this->payment(
            'surplus-refund-payment-test-001',
            50000,
            'idempotency-test-001',
            'audit-event-payment-test-001',
        ));

        $payment = $reader->findActiveByDispositionIdAndIdempotencyKey(
            'surplus-disposition-test-001',
            'idempotency-test-001',
        );

        self::assertNotNull($payment);
        self::assertSame('surplus-refund-payment-test-001', $payment->id);
        self::assertSame('surplus-disposition-test-001', $payment->noteRevisionSurplusDispositionId);
        self::assertSame(50000, $payment->amountRupiah);
        self::assertSame('2026-05-13', $payment->effectiveDateString());

        $this->assertDatabaseHas('note_revision_surplus_refund_payments', [
            'id' => 'surplus-refund-payment-test-001',
            'note_revision_surplus_disposition_id' => 'surplus-disposition-test-001',
            'note_revision_settlement_id' => 'settlement-test-001',
            'note_root_id' => 'note-root-test-001',
            'note_revision_id' => 'note-revision-test-001',
            'amount_rupiah' => 50000,
            'effective_date' => '2026-05-13',
            'status' => 'active',
            'idempotency_key' => 'idempotency-test-001',
            'audit_event_id' => 'audit-event-payment-test-001',
        ]);
    }

    public function test_source_reader_locks_active_refund_due_and_subtracts_active_refund_payments(): void
    {
        $this->seedRefundDueDisposition();
        $this->seedAuditEvent(
            'audit-event-payment-test-002',
            'note_revision_surplus_refund_payment',
            'surplus-refund-payment-test-002',
            'note_revision_surplus_refund_paid_recorded',
        );

        $writer = $this->app->make(NoteRevisionSurplusRefundPaymentWriterPort::class);
        $sourceReader = $this->app->make(NoteRevisionSurplusRefundDueSourceReaderPort::class);

        $writer->create($this->payment(
            'surplus-refund-payment-test-002',
            50000,
            'idempotency-test-002',
            'audit-event-payment-test-002',
        ));

        DB::transaction(function () use ($sourceReader): void {
            $source = $sourceReader->findActiveRefundDueByDispositionIdForUpdate(
                'surplus-disposition-test-001',
            );

            self::assertNotNull($source);
            self::assertSame('surplus-disposition-test-001', $source->dispositionId);
            self::assertSame(122000, $source->refundDueRupiah);
            self::assertSame(50000, $source->activeRefundPaidRupiah);
            self::assertSame(72000, $source->remainingRefundDueRupiah);
        });
    }

    public function test_payment_reader_sums_only_active_refund_payments(): void
    {
        $this->seedRefundDueDisposition();
        $this->seedAuditEvent(
            'audit-event-payment-test-003',
            'note_revision_surplus_refund_payment',
            'surplus-refund-payment-test-003',
            'note_revision_surplus_refund_paid_recorded',
        );
        $this->seedAuditEvent(
            'audit-event-payment-test-004',
            'note_revision_surplus_refund_payment',
            'surplus-refund-payment-test-004',
            'note_revision_surplus_refund_paid_recorded',
        );

        $writer = $this->app->make(NoteRevisionSurplusRefundPaymentWriterPort::class);
        $reader = $this->app->make(NoteRevisionSurplusRefundPaymentReaderPort::class);

        $writer->create($this->payment(
            'surplus-refund-payment-test-003',
            50000,
            'idempotency-test-003',
            'audit-event-payment-test-003',
        ));

        DB::table('note_revision_surplus_refund_payments')->insert([
            'id' => 'surplus-refund-payment-test-004',
            'note_revision_surplus_disposition_id' => 'surplus-disposition-test-001',
            'note_revision_settlement_id' => 'settlement-test-001',
            'note_root_id' => 'note-root-test-001',
            'note_revision_id' => 'note-revision-test-001',
            'amount_rupiah' => 70000,
            'effective_date' => '2026-05-13',
            'occurred_at' => '2026-05-13 11:00:00',
            'status' => 'reversed',
            'idempotency_key' => 'idempotency-test-004',
            'audit_event_id' => 'audit-event-payment-test-004',
            'created_at' => '2026-05-13 11:00:00',
            'updated_at' => null,
        ]);

        self::assertSame(50000, $reader->sumActiveAmountByDispositionId('surplus-disposition-test-001'));
    }

    private function payment(
        string $id,
        int $amountRupiah,
        string $idempotencyKey,
        string $auditEventId,
    ): NoteRevisionSurplusRefundPayment {
        return NoteRevisionSurplusRefundPayment::create(
            $id,
            'surplus-disposition-test-001',
            'settlement-test-001',
            'note-root-test-001',
            'note-revision-test-001',
            $amountRupiah,
            new DateTimeImmutable('2026-05-13'),
            new DateTimeImmutable('2026-05-13 10:30:00'),
            NoteRevisionSurplusRefundPayment::STATUS_ACTIVE,
            $idempotencyKey,
            $auditEventId,
            new DateTimeImmutable('2026-05-13 10:30:00'),
        );
    }

    private function seedRefundDueDisposition(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-root-test-001',
            'customer_name' => 'Customer Test',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'note_state' => 'closed',
            'closed_at' => '2026-05-13 09:00:00',
            'closed_by_actor_id' => 'admin-test-001',
            'reopened_at' => null,
            'reopened_by_actor_id' => null,
            'total_rupiah' => 143000,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-revision-test-001',
            'note_root_id' => 'note-root-test-001',
            'revision_number' => 2,
            'parent_revision_id' => null,
            'created_by_actor_id' => 'admin-test-001',
            'reason' => 'Test revision surplus.',
            'customer_name' => 'Customer Test',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'grand_total_rupiah' => 143000,
            'line_count' => 1,
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);

        DB::table('note_revision_settlements')->insert([
            'id' => 'settlement-test-001',
            'note_revision_id' => 'note-revision-test-001',
            'note_root_id' => 'note-root-test-001',
            'gross_total_rupiah' => 143000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 265000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 122000,
            'settlement_status' => 'overpaid_pending',
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);

        $this->seedAuditEvent(
            'audit-event-disposition-test-001',
            'note_revision_surplus_disposition',
            'surplus-disposition-test-001',
            'note_revision_surplus_refund_due_created',
        );

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => 'surplus-disposition-test-001',
            'note_revision_settlement_id' => 'settlement-test-001',
            'note_root_id' => 'note-root-test-001',
            'note_revision_id' => 'note-revision-test-001',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 122000,
            'before_pending_rupiah' => 122000,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2026-05-13 10:00:00',
            'created_at' => '2026-05-13 10:00:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-event-disposition-test-001',
        ]);
    }

    private function seedAuditEvent(
        string $auditEventId,
        string $aggregateType,
        string $aggregateId,
        string $eventName,
    ): void {
        DB::table('audit_events')->insert([
            'id' => $auditEventId,
            'bounded_context' => 'note',
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_name' => $eventName,
            'actor_id' => 'admin-test-001',
            'actor_role' => 'admin',
            'reason' => 'Customer requested surplus refund.',
            'source_channel' => 'web_admin',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-05-13 10:00:00',
            'metadata_json' => null,
        ]);
    }
}
