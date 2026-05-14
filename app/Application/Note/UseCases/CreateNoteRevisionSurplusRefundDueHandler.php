<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CreateNoteRevisionSurplusRefundDueHandler
{
    public function __construct(
        private readonly NoteRevisionSurplusDispositionReaderPort $reader,
        private readonly NoteRevisionSurplusDispositionWriterPort $writer,
        private readonly AuditEventWriterPort $auditWriter,
        private readonly TransactionManagerPort $transactions,
        private readonly CreateNoteRevisionSurplusRefundDueGuard $guard,
        private readonly CreateNoteRevisionSurplusRefundDueDispositionFactory $dispositionFactory,
        private readonly CreateNoteRevisionSurplusRefundDueAuditEventFactory $auditFactory,
        private readonly CreateNoteRevisionSurplusRefundDueResultFactory $resultFactory,
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
                $this->reader->findPendingBySettlementIdForUpdate($command->noteRevisionSettlementId),
            );

            $this->guard->assertAmountFits($command->amountRupiah, $pending);

            $disposition = $this->dispositionFactory->create($command, $pending);
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

            return $this->resultFactory->success($disposition, $after);
        } catch (DomainException $e) {
            $this->rollBackIfStarted($started);

            return CreateNoteRevisionSurplusRefundDueResult::failure($e->getMessage());
        } catch (Throwable $e) {
            $this->rollBackIfStarted($started);

            throw $e;
        }
    }

    private function rollBackIfStarted(bool $started): void
    {
        if ($started) {
            $this->transactions->rollBack();
        }
    }
}
