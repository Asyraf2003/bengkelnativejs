<?php

declare(strict_types=1);

namespace Tests\Feature\AuditLog;

use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class DatabaseAuditOutboxWriterAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_writer_persists_pending_outbox_row_from_audit_event_write(): void
    {
        $writer = $this->app->make(DatabaseAuditOutboxWriterAdapter::class);

        $writer->write($this->event());

        $row = DB::table('audit_outbox')
            ->where('audit_event_id', 'audit-outbox-event-001')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('expense', $row->bounded_context);
        $this->assertSame('expense_category', $row->aggregate_type);
        $this->assertSame('cat-1', $row->aggregate_id);
        $this->assertSame('expense_category_updated', $row->event_name);
        $this->assertSame('admin-1', $row->actor_id);
        $this->assertSame('pending', $row->status);
        $this->assertSame(0, (int) $row->attempts);
        $this->assertNull($row->last_error);
        $this->assertNull($row->locked_at);
        $this->assertNull($row->processed_at);

        $metadata = json_decode((string) $row->metadata_json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('cat-1', $metadata['category_id']);
        $this->assertSame('admin-1', $metadata['performed_by_actor_id']);

        $snapshots = json_decode((string) $row->snapshots_json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $snapshots);
        $this->assertSame('before', $snapshots[0]['snapshot_kind']);
        $this->assertSame('after', $snapshots[1]['snapshot_kind']);
        $this->assertFalse($snapshots[0]['payload']['is_active']);
        $this->assertTrue($snapshots[1]['payload']['is_active']);
    }

    public function test_writer_does_not_materialize_canonical_audit_tables(): void
    {
        $writer = $this->app->make(DatabaseAuditOutboxWriterAdapter::class);

        $writer->write($this->event());

        $this->assertDatabaseCount('audit_outbox', 1);
        $this->assertDatabaseCount('audit_events', 0);
        $this->assertDatabaseCount('audit_event_snapshots', 0);
    }

    public function test_writer_participates_in_outer_database_transaction(): void
    {
        $writer = $this->app->make(DatabaseAuditOutboxWriterAdapter::class);

        DB::beginTransaction();

        try {
            $writer->write($this->event());

            throw new RuntimeException('force rollback after audit outbox write');
        } catch (RuntimeException) {
            DB::rollBack();
        }

        $this->assertDatabaseMissing('audit_outbox', [
            'audit_event_id' => 'audit-outbox-event-001',
        ]);
    }

    private function event(): AuditEventWrite
    {
        return new AuditEventWrite(
            id: 'audit-outbox-event-001',
            boundedContext: 'expense',
            aggregateType: 'expense_category',
            aggregateId: 'cat-1',
            eventName: 'expense_category_updated',
            actorId: 'admin-1',
            actorRole: null,
            reason: null,
            sourceChannel: 'web_admin',
            requestId: 'request-1',
            correlationId: 'correlation-1',
            occurredAt: new DateTimeImmutable('2026-05-23 10:00:00'),
            metadata: [
                'category_id' => 'cat-1',
                'performed_by_actor_id' => 'admin-1',
            ],
            snapshots: [
                new AuditEventSnapshotWrite('before', [
                    'id' => 'cat-1',
                    'code' => 'EXP-ELEC',
                    'name' => 'Listrik',
                    'description' => null,
                    'is_active' => false,
                ]),
                new AuditEventSnapshotWrite('after', [
                    'id' => 'cat-1',
                    'code' => 'EXP-UTIL',
                    'name' => 'Utilitas',
                    'description' => null,
                    'is_active' => true,
                ]),
            ],
        );
    }
}
