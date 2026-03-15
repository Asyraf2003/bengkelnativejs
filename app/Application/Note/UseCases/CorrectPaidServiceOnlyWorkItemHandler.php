<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Policies\NotePaidStatusPolicy;
use App\Application\Note\Services\NoteCorrectionSnapshotBuilder;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\Note\WorkItemWriterPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CorrectPaidServiceOnlyWorkItemHandler
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly WorkItemWriterPort $workItems,
        private readonly NoteWriterPort $noteWriter,
        private readonly TransactionManagerPort $transactions,
        private readonly NotePaidStatusPolicy $paidStatus,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly CustomerRefundReaderPort $refunds,
        private readonly AuditLogPort $audit,
    ) {
    }

    public function handle(
        string $noteId,
        int $lineNo,
        string $serviceName,
        int $servicePriceRupiah,
        string $partSource,
        string $reason,
        string $performedByActorId,
    ): Result {
        $started = false;

        try {
            if ($lineNo <= 0) {
                throw new DomainException('Line number harus > 0.');
            }

            if (trim($reason) === '') {
                return Result::failure(
                    'Alasan correction wajib diisi.',
                    ['correction' => ['AUDIT_REASON_REQUIRED']],
                );
            }

            if (trim($performedByActorId) === '') {
                throw new DomainException('Actor correction wajib ada.');
            }

            $this->transactions->begin();
            $started = true;

            $note = $this->notes->getById(trim($noteId))
                ?? throw new DomainException('Note tidak ditemukan.');

            $this->paidStatus->assertPaidForCorrection($note);

            $target = $this->findWorkItem($note, $lineNo);

            if ($target->transactionType() !== WorkItem::TYPE_SERVICE_ONLY) {
                throw new DomainException('Correction nominal slice ini hanya mendukung work item service_only.');
            }

            $before = $this->snapshots->build($note);

            $newServiceDetail = ServiceDetail::create(
                $serviceName,
                Money::fromInt($servicePriceRupiah),
                $partSource,
            );

            $correctedWorkItem = WorkItem::rehydrate(
                $target->id(),
                $target->noteId(),
                $target->lineNo(),
                $target->transactionType(),
                $target->status(),
                $newServiceDetail->servicePriceRupiah(),
                $newServiceDetail,
                [],
                [],
            );

            $newTotal = $note->totalRupiah()
                ->subtract($target->subtotalRupiah())
                ->add($correctedWorkItem->subtotalRupiah());

            $newTotal->ensureNotNegative('Total note hasil correction tidak boleh negatif.');

            $note->syncTotalRupiah($newTotal);

            $this->workItems->updateServiceOnly($correctedWorkItem);
            $this->noteWriter->updateTotal($note);

            $afterNote = $this->notes->getById($note->id())
                ?? throw new DomainException('Note tidak ditemukan setelah correction.');

            $after = $this->snapshots->build($afterNote);

            $allocated = $this->allocations->getTotalAllocatedAmountByNoteId($note->id());
            $refunded = $this->refunds->getTotalRefundedAmountByNoteId($note->id());
            $netSettlement = $allocated->subtract($refunded);
            $netSettlement->ensureNotNegative('Net settlement pada note tidak boleh negatif.');

            $refundRequired = 0;
            if ($netSettlement->greaterThan($afterNote->totalRupiah())) {
                $refundRequired = $netSettlement
                    ->subtract($afterNote->totalRupiah())
                    ->amount();
            }

            $this->audit->record('paid_service_only_work_item_corrected', [
                'performed_by_actor_id' => trim($performedByActorId),
                'note_id' => $note->id(),
                'line_no' => $lineNo,
                'reason' => trim($reason),
                'refund_required_rupiah' => $refundRequired,
                'before' => $before,
                'after' => $after,
            ]);

            $this->transactions->commit();

            return Result::success(
                [
                    'note' => [
                        'id' => $afterNote->id(),
                        'total_rupiah' => $afterNote->totalRupiah()->amount(),
                    ],
                    'work_item' => [
                        'id' => $correctedWorkItem->id(),
                        'line_no' => $correctedWorkItem->lineNo(),
                        'transaction_type' => $correctedWorkItem->transactionType(),
                        'status' => $correctedWorkItem->status(),
                        'subtotal_rupiah' => $correctedWorkItem->subtotalRupiah()->amount(),
                    ],
                    'refund_required_rupiah' => $refundRequired,
                ],
                'Correction nominal service_only berhasil disimpan.',
            );
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return Result::failure(
                $e->getMessage(),
                ['work_item' => ['INVALID_WORK_ITEM_STATE']],
            );
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }

    private function findWorkItem($note, int $lineNo): WorkItem
    {
        foreach ($note->workItems() as $item) {
            if ($item->lineNo() === $lineNo) {
                return $item;
            }
        }

        throw new DomainException('Work item pada note tidak ditemukan.');
    }
}
