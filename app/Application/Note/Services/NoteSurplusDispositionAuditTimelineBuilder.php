<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Ports\Out\Note\NoteSurplusDispositionAuditTimelineReaderPort;

final class NoteSurplusDispositionAuditTimelineBuilder
{
    public function __construct(
        private readonly NoteSurplusDispositionAuditTimelineReaderPort $timeline,
    ) {
    }

    /**
     * @return list<array{
     *   label:string,
     *   event_name:string,
     *   disposition_id:string,
     *   note_revision_settlement_id:string,
     *   note_revision_id:string,
     *   disposition_type:string,
     *   amount_rupiah:int,
     *   before_pending_rupiah:int,
     *   after_pending_rupiah:int,
     *   actor_id:?string,
     *   actor_role:?string,
     *   reason:?string,
     *   occurred_at:string
     * }>
     */
    public function build(string $noteRootId): array
    {
        return array_map(
            static fn (array $row): array => [
                'label' => 'Refund Due Ditandai',
                'event_name' => (string) $row['event_name'],
                'disposition_id' => (string) $row['disposition_id'],
                'note_revision_settlement_id' => (string) $row['note_revision_settlement_id'],
                'note_revision_id' => (string) $row['note_revision_id'],
                'disposition_type' => (string) $row['disposition_type'],
                'amount_rupiah' => (int) $row['amount_rupiah'],
                'before_pending_rupiah' => (int) $row['before_pending_rupiah'],
                'after_pending_rupiah' => (int) $row['after_pending_rupiah'],
                'actor_id' => $row['actor_id'],
                'actor_role' => $row['actor_role'],
                'reason' => $row['reason'],
                'occurred_at' => (string) $row['occurred_at'],
            ],
            $this->timeline->findRefundDueCreatedEventsByNoteRootId($noteRootId),
        );
    }
}
