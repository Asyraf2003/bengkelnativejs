<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\WorkItemWriterPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class UpdateWorkItemStatusHandler
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly WorkItemWriterPort $workItems,
        private readonly TransactionManagerPort $transactions,
    ) {
    }

    public function handle(
        string $noteId,
        int $lineNo,
        string $targetStatus,
    ): Result {
        try {
            $normalizedNoteId = $this->normalizeRequired(
                $noteId,
                'Note id pada update status work item wajib ada.'
            );

            $normalizedTargetStatus = $this->normalizeRequired(
                $targetStatus,
                'Target status work item wajib ada.'
            );

            if ($lineNo <= 0) {
                throw new DomainException('Line number pada update status work item harus lebih besar dari nol.');
            }
        } catch (DomainException $e) {
            return Result::failure(
                $e->getMessage(),
                ['work_item' => ['INVALID_WORK_ITEM']]
            );
        }

        $transactionStarted = false;

        try {
            $this->transactions->begin();
            $transactionStarted = true;

            $note = $this->notes->getById($normalizedNoteId);

            if ($note === null) {
                throw new DomainException('Note tidak ditemukan.');
            }

            $workItem = $this->findWorkItemByLineNo($note->workItems(), $lineNo);

            $this->applyTargetStatus($workItem, $normalizedTargetStatus);

            $this->workItems->updateStatus($workItem);

            $this->transactions->commit();

            return Result::success(
                [
                    'note' => [
                        'id' => $note->id(),
                        'customer_name' => $note->customerName(),
                        'transaction_date' => $note->transactionDate()->format('Y-m-d'),
                    ],
                    'work_item' => [
                        'id' => $workItem->id(),
                        'note_id' => $workItem->noteId(),
                        'line_no' => $workItem->lineNo(),
                        'transaction_type' => $workItem->transactionType(),
                        'status' => $workItem->status(),
                        'subtotal_rupiah' => $workItem->subtotalRupiah()->amount(),
                    ],
                ],
                'Status work item berhasil diperbarui.'
            );
        } catch (DomainException $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            return Result::failure(
                $e->getMessage(),
                ['work_item' => ['INVALID_WORK_ITEM']]
            );
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param list<WorkItem> $workItems
     */
    private function findWorkItemByLineNo(array $workItems, int $lineNo): WorkItem
    {
        foreach ($workItems as $workItem) {
            if ($workItem->lineNo() === $lineNo) {
                return $workItem;
            }
        }

        throw new DomainException('Work item pada note tidak ditemukan.');
    }

    private function applyTargetStatus(WorkItem $workItem, string $targetStatus): void
    {
        if ($targetStatus === WorkItem::STATUS_DONE) {
            $workItem->markDone();
            return;
        }

        if ($targetStatus === WorkItem::STATUS_CANCELED) {
            $workItem->cancel();
            return;
        }

        if ($targetStatus === WorkItem::STATUS_OPEN && $workItem->status() === WorkItem::STATUS_OPEN) {
            return;
        }

        throw new DomainException('Target status work item belum didukung pada slice ini.');
    }

    private function normalizeRequired(string $value, string $message): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new DomainException($message);
        }

        return $normalized;
    }
}
