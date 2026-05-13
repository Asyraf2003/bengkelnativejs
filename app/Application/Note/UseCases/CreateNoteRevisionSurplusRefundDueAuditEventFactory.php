<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\DTO\NoteRevisionSurplusPending;

final class CreateNoteRevisionSurplusRefundDueAuditEventFactory
{
    public function create(
        string $auditEventId,
        NoteRevisionSurplusDisposition $disposition,
        NoteRevisionSurplusPending $before,
        string $actorId,
        string $actorRole,
        string $reason,
        ?string $sourceChannel,
        ?string $requestId,
        ?string $correlationId,
    ): AuditEventWrite {
        $afterActiveDisposition = $before->activeDispositionRupiah + $disposition->amountRupiah;

        return new AuditEventWrite(
            id: $auditEventId,
            boundedContext: 'note',
            aggregateType: 'note_revision_surplus_disposition',
            aggregateId: $disposition->id,
            eventName: 'note_revision_surplus_refund_due_created',
            actorId: trim($actorId),
            actorRole: trim($actorRole),
            reason: trim($reason),
            sourceChannel: $sourceChannel,
            requestId: $requestId,
            correlationId: $correlationId,
            occurredAt: $disposition->occurredAt,
            metadata: [
                'note_root_id' => $disposition->noteRootId,
                'note_revision_id' => $disposition->noteRevisionId,
                'note_revision_settlement_id' => $disposition->noteRevisionSettlementId,
                'disposition_id' => $disposition->id,
                'disposition_type' => $disposition->dispositionType,
                'amount_rupiah' => $disposition->amountRupiah,
            ],
            snapshots: [
                new AuditEventSnapshotWrite('before', [
                    'surplus_rupiah' => $before->surplusRupiah,
                    'active_disposition_rupiah' => $before->activeDispositionRupiah,
                    'pending_surplus_rupiah' => $before->unresolvedPendingRupiah,
                ]),
                new AuditEventSnapshotWrite('after', [
                    'surplus_rupiah' => $before->surplusRupiah,
                    'active_disposition_rupiah' => $afterActiveDisposition,
                    'pending_surplus_rupiah' => $disposition->afterPendingRupiah,
                ]),
            ],
        );
    }
}
