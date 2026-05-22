<?php

declare(strict_types=1);

namespace App\Adapters\Out\Audit;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\UuidPort;
use Illuminate\Support\Facades\DB;

final class DatabaseAuditOutboxWriterAdapter implements AuditEventWriterPort
{
    private const STATUS_PENDING = 'pending';

    public function __construct(
        private readonly UuidPort $uuid,
        private readonly ClockPort $clock,
    ) {
    }

    public function write(AuditEventWrite $event): void
    {
        $now = $this->clock->now();

        DB::table('audit_outbox')->insert([
            'id' => $this->uuid->generate(),
            'audit_event_id' => $event->id(),
            'bounded_context' => $event->boundedContext(),
            'aggregate_type' => $event->aggregateType(),
            'aggregate_id' => $event->aggregateId(),
            'event_name' => $event->eventName(),
            'actor_id' => $event->actorId(),
            'actor_role' => $event->actorRole(),
            'reason' => $event->reason(),
            'source_channel' => $event->sourceChannel(),
            'request_id' => $event->requestId(),
            'correlation_id' => $event->correlationId(),
            'occurred_at' => $event->occurredAt(),
            'metadata_json' => $this->nullableJson($event->metadata()),
            'snapshots_json' => $this->snapshotsJson($event->snapshots()),
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
            'last_error' => null,
            'available_at' => null,
            'locked_at' => null,
            'processed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param array<mixed> $payload
     */
    private function nullableJson(array $payload): ?string
    {
        if ($payload === []) {
            return null;
        }

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param list<AuditEventSnapshotWrite> $snapshots
     */
    private function snapshotsJson(array $snapshots): ?string
    {
        if ($snapshots === []) {
            return null;
        }

        $payload = [];

        foreach ($snapshots as $snapshot) {
            $payload[] = [
                'snapshot_kind' => $snapshot->kind(),
                'payload' => $snapshot->payload(),
            ];
        }

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
