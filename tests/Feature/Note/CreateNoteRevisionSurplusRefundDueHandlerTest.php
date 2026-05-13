<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueCommand;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueHandler;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class CreateNoteRevisionSurplusRefundDueHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_non_admin_actor(): void
    {
        $this->seedSourceSettlement('settlement-refund-due-001', 122000);

        $result = $this->handler()->handle($this->command(
            settlementId: 'settlement-refund-due-001',
            actorRole: 'cashier',
        ));

        self::assertTrue($result->isFailure());
        self::assertSame('Surplus refund_due hanya boleh dibuat oleh admin.', $result->message());
        $this->assertDatabaseMissing('audit_events', ['event_name' => 'note_revision_surplus_refund_due_created']);
        $this->assertDatabaseMissing('note_revision_surplus_dispositions', ['note_revision_settlement_id' => 'settlement-refund-due-001']);
    }

    public function test_rejects_empty_reason(): void
    {
        $this->seedSourceSettlement('settlement-refund-due-002', 122000);

        $result = $this->handler()->handle($this->command(
            settlementId: 'settlement-refund-due-002',
            reason: '   ',
        ));

        self::assertTrue($result->isFailure());
        self::assertSame('Alasan refund_due wajib diisi.', $result->message());
        $this->assertDatabaseMissing('note_revision_surplus_dispositions', ['note_revision_settlement_id' => 'settlement-refund-due-002']);
    }

    public function test_rejects_missing_or_invalid_pending_settlement(): void
    {
        $missing = $this->handler()->handle($this->command(settlementId: 'missing-settlement'));

        $this->seedSourceSettlement('settlement-refund-due-003', 0, 'paid');

        $invalid = $this->handler()->handle($this->command(settlementId: 'settlement-refund-due-003'));

        self::assertTrue($missing->isFailure());
        self::assertTrue($invalid->isFailure());
        self::assertSame('Pending surplus settlement tidak valid atau sudah selesai.', $missing->message());
        self::assertSame('Pending surplus settlement tidak valid atau sudah selesai.', $invalid->message());
    }

    public function test_rejects_amount_greater_than_unresolved_pending(): void
    {
        $this->seedSourceSettlement('settlement-refund-due-004', 122000);

        $result = $this->handler()->handle($this->command(
            settlementId: 'settlement-refund-due-004',
            amountRupiah: 122001,
        ));

        self::assertTrue($result->isFailure());
        self::assertSame('Nominal refund_due melebihi pending surplus.', $result->message());
        $this->assertDatabaseMissing('note_revision_surplus_dispositions', ['note_revision_settlement_id' => 'settlement-refund-due-004']);
    }

    public function test_writes_audit_event_snapshots_disposition_and_updates_pending(): void
    {
        $this->seedSourceSettlement('settlement-refund-due-005', 122000);

        $result = $this->handler()->handle($this->command(
            settlementId: 'settlement-refund-due-005',
            amountRupiah: 50000,
        ));

        self::assertTrue($result->isSuccess());
        self::assertSame(72000, $result->data()['unresolved_pending_rupiah']);

        $this->assertDatabaseHas('audit_events', [
            'id' => 'audit-event-refund-due-test-001',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => 'disposition-refund-due-test-001',
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => 'admin-test-001',
            'actor_role' => 'admin',
            'reason' => 'Customer requested refund due.',
            'source_channel' => 'web_admin',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-refund-due-test-001',
            'snapshot_kind' => 'before',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-refund-due-test-001',
            'snapshot_kind' => 'after',
        ]);

        $this->assertDatabaseHas('note_revision_surplus_dispositions', [
            'id' => 'disposition-refund-due-test-001',
            'note_revision_settlement_id' => 'settlement-refund-due-005',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 50000,
            'before_pending_rupiah' => 122000,
            'after_pending_rupiah' => 72000,
            'status' => 'active',
            'audit_event_id' => 'audit-event-refund-due-test-001',
        ]);

        $reader = $this->app->make(NoteRevisionSurplusDispositionReaderPort::class);
        $pending = $reader->findPendingBySettlementId('settlement-refund-due-005');

        self::assertNotNull($pending);
        self::assertSame(72000, $pending->unresolvedPendingRupiah);
    }

    public function test_rolls_back_audit_event_and_disposition_when_second_write_fails(): void
    {
        $this->seedSourceSettlement('settlement-refund-due-006', 122000);
        $this->bindDeterministicPorts();
        $this->app->instance(NoteRevisionSurplusDispositionWriterPort::class, new FailingRefundDueDispositionWriter());

        try {
            $this->app->make(CreateNoteRevisionSurplusRefundDueHandler::class)
                ->handle($this->command(settlementId: 'settlement-refund-due-006'));

            self::fail('Expected failing disposition writer to throw.');
        } catch (RuntimeException $e) {
            self::assertSame('force rollback after disposition write', $e->getMessage());
        }

        $this->assertDatabaseMissing('audit_events', ['id' => 'audit-event-refund-due-test-001']);
        $this->assertDatabaseMissing('audit_event_snapshots', ['audit_event_id' => 'audit-event-refund-due-test-001']);
        $this->assertDatabaseMissing('note_revision_surplus_dispositions', ['id' => 'disposition-refund-due-test-001']);
    }

    private function handler(): CreateNoteRevisionSurplusRefundDueHandler
    {
        $this->bindDeterministicPorts();

        return $this->app->make(CreateNoteRevisionSurplusRefundDueHandler::class);
    }

    private function bindDeterministicPorts(): void
    {
        $this->app->instance(UuidPort::class, new SequentialRefundDueUuidPort([
            'disposition-refund-due-test-001',
            'audit-event-refund-due-test-001',
            'audit-snapshot-refund-due-before-001',
            'audit-snapshot-refund-due-after-001',
        ]));

        $this->app->instance(
            ClockPort::class,
            new FixedRefundDueClockPort(new DateTimeImmutable('2026-05-13 10:00:00')),
        );

        $this->app->forgetInstance(CreateNoteRevisionSurplusRefundDueHandler::class);
    }

    private function command(
        string $settlementId,
        int $amountRupiah = 122000,
        string $reason = 'Customer requested refund due.',
        string $actorRole = 'admin',
    ): CreateNoteRevisionSurplusRefundDueCommand {
        return new CreateNoteRevisionSurplusRefundDueCommand(
            noteRevisionSettlementId: $settlementId,
            amountRupiah: $amountRupiah,
            reason: $reason,
            actorId: 'admin-test-001',
            actorRole: $actorRole,
            occurredAt: null,
            sourceChannel: 'web_admin',
            requestId: null,
            correlationId: null,
        );
    }

    private function seedSourceSettlement(
        string $settlementId,
        int $surplusRupiah,
        string $status = 'overpaid_pending',
    ): void {
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
            'id' => $settlementId,
            'note_revision_id' => 'note-revision-test-001',
            'note_root_id' => 'note-root-test-001',
            'gross_total_rupiah' => 143000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 265000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => $surplusRupiah,
            'settlement_status' => $status,
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);
    }
}

final class SequentialRefundDueUuidPort implements UuidPort
{
    /** @param list<string> $ids */
    public function __construct(private array $ids)
    {
    }

    public function generate(): string
    {
        return array_shift($this->ids) ?? 'generated-refund-due-id';
    }
}

final class FixedRefundDueClockPort implements ClockPort
{
    public function __construct(private readonly DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final class FailingRefundDueDispositionWriter implements NoteRevisionSurplusDispositionWriterPort
{
    public function create(NoteRevisionSurplusDisposition $disposition): void
    {
        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => $disposition->id,
            'note_revision_settlement_id' => $disposition->noteRevisionSettlementId,
            'note_root_id' => $disposition->noteRootId,
            'note_revision_id' => $disposition->noteRevisionId,
            'disposition_type' => $disposition->dispositionType,
            'amount_rupiah' => $disposition->amountRupiah,
            'before_pending_rupiah' => $disposition->beforePendingRupiah,
            'after_pending_rupiah' => $disposition->afterPendingRupiah,
            'status' => $disposition->status,
            'occurred_at' => $disposition->occurredAt->format('Y-m-d H:i:s'),
            'created_at' => $disposition->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => null,
            'audit_event_id' => $disposition->auditEventId,
        ]);

        throw new RuntimeException('force rollback after disposition write');
    }
}
