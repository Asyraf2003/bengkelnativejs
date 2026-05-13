<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\DTO\NoteRevisionSurplusPending;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Throwable;

final class CreateNoteRevisionSurplusRefundDueHandler
{
    public function __construct(
        private readonly NoteRevisionSurplusDispositionReaderPort $reader,
        private readonly NoteRevisionSurplusDispositionWriterPort $writer,
        private readonly AuditEventWriterPort $auditWriter,
        private readonly TransactionManagerPort $transactions,
        private readonly UuidPort $uuid,
        private readonly ClockPort $clock,
        private readonly CreateNoteRevisionSurplusRefundDueGuard $guard,
        private readonly CreateNoteRevisionSurplusRefundDueAuditEventFactory $auditFactory,
    ) {
    }

    public function handle(
        CreateNoteRevisionSurplusRefundDueCommand $command,
    ): CreateNoteRevisionSurplusRefundDueResult {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;

            $reason = $this->guard->assertCommandAllowed($command);
            $pending = $this->guard->pendingOrFail(
                $this->reader->findPendingBySettlementId($command->noteRevisionSettlementId),
            );
            $this->guard->assertAmountFits($command->amountRupiah, $pending);

            $disposition = $this->createDisposition($command, $pending);
            $this->auditWriter->write($this->auditFactory->create(
                $disposition->auditEventId,
                $disposition,
                $pending,
                $command->actorId,
                $command->actorRole,
                $reason,
                $command->sourceChannel,
                $command->requestId,
                $command->correlationId,
            ));
            $this->writer->create($disposition);

            $after = $this->reader->findPendingBySettlementId($pending->noteRevisionSettlementId);

            $this->transactions->commit();

            return CreateNoteRevisionSurplusRefundDueResult::success(
                $this->resultData($disposition, $after),
            );
        } catch (DomainException $e) {
            $this->rollBackIfStarted($started);

            return CreateNoteRevisionSurplusRefundDueResult::failure($e->getMessage());
        } catch (Throwable $e) {
            $this->rollBackIfStarted($started);

            throw $e;
        }
    }

    private function createDisposition(
        CreateNoteRevisionSurplusRefundDueCommand $command,
        NoteRevisionSurplusPending $pending,
    ): NoteRevisionSurplusDisposition {
        $occurredAt = $command->occurredAt ?? $this->clock->now();

        return NoteRevisionSurplusDisposition::create(
            $this->uuid->generate(),
            $pending->noteRevisionSettlementId,
            $pending->noteRootId,
            $pending->noteRevisionId,
            NoteRevisionSurplusDisposition::TYPE_REFUND_DUE,
            $command->amountRupiah,
            $pending->unresolvedPendingRupiah,
            $pending->unresolvedPendingRupiah - $command->amountRupiah,
            NoteRevisionSurplusDisposition::STATUS_ACTIVE,
            $occurredAt,
            $this->clock->now(),
            $this->uuid->generate(),
        );
    }

    private function rollBackIfStarted(bool $started): void
    {
        if ($started) {
            $this->transactions->rollBack();
        }
    }

    /** @return array<string, mixed> */
    private function resultData(
        NoteRevisionSurplusDisposition $disposition,
        ?NoteRevisionSurplusPending $after,
    ): array {
        return [
            'disposition_id' => $disposition->id,
            'note_revision_settlement_id' => $disposition->noteRevisionSettlementId,
            'note_root_id' => $disposition->noteRootId,
            'note_revision_id' => $disposition->noteRevisionId,
            'disposition_type' => $disposition->dispositionType,
            'amount_rupiah' => $disposition->amountRupiah,
            'before_pending_rupiah' => $disposition->beforePendingRupiah,
            'after_pending_rupiah' => $disposition->afterPendingRupiah,
            'unresolved_pending_rupiah' => $after?->unresolvedPendingRupiah,
            'status' => $disposition->status,
            'audit_event_id' => $disposition->auditEventId,
        ];
    }
}
