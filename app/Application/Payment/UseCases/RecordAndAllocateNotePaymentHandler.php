<?php

declare(strict_types=1);

namespace App\Application\Payment\UseCases;

use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Payment\Services\AllocatePaymentErrorClassifier;
use App\Application\Payment\Services\PaymentConcurrencyTransientExceptionClassifier;
use App\Application\Payment\Services\RecordAndAllocateNotePaymentOperation;
use App\Application\Shared\DTO\Result;
use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RecordAndAllocateNotePaymentHandler
{
    public function __construct(
        private readonly RecordAndAllocateNotePaymentOperation $operation,
        private readonly TransactionManagerPort $transactions,
        private readonly AllocatePaymentErrorClassifier $errors,
        private readonly AuditLogPort $audit,
        private readonly NoteHistoryProjectionService $projection,
        private readonly PaymentConcurrencyTransientExceptionClassifier $concurrencyErrors,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     */
    public function handle(
        string $noteId,
        int $amountRupiah,
        string $paidAt,
        array $selectedRowIds = [],
        string $paymentMethod = CustomerPayment::METHOD_UNKNOWN,
        ?int $amountReceivedRupiah = null,
    ): Result {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $started = false;

            try {
                $this->transactions->begin();
                $started = true;

                $recorded = $this->operation->execute(
                    $noteId,
                    $amountRupiah,
                    $paidAt,
                    $selectedRowIds,
                    $paymentMethod,
                    $amountReceivedRupiah,
                );

                $this->audit->record('payment_allocated', [
                    'payment_id' => $recorded->payment()->id(),
                    'note_id' => trim($noteId),
                    'amount' => $amountRupiah,
                    'payment_method' => $recorded->payment()->paymentMethod(),
                    'amount_received' => $amountReceivedRupiah,
                    'change' => $this->changeAmount($recorded->payment()->paymentMethod(), $amountRupiah, $amountReceivedRupiah),
                    'allocation_count' => $recorded->allocationCount(),
                    'selected_row_ids' => $selectedRowIds,
                ]);

                $this->projection->syncNote(trim($noteId));

                $this->transactions->commit();

                return Result::success([
                    'payment_id' => $recorded->payment()->id(),
                    'allocation_count' => $recorded->allocationCount(),
                ], 'Pembayaran berhasil dicatat.');
            } catch (DomainException $e) {
                if ($started) {
                    $this->transactions->rollBack();
                }

                return $this->errors->classify($e);
            } catch (Throwable $e) {
                if ($started) {
                    $this->transactions->rollBack();
                }

                if ($attempt < 3 && $this->concurrencyErrors->isTransient($e)) {
                    usleep(25000 * $attempt);

                    continue;
                }

                throw $e;
            }
        }

        throw new \LogicException('Unreachable payment concurrency retry state.');
    }

    private function changeAmount(string $paymentMethod, int $amountRupiah, ?int $amountReceivedRupiah): ?int
    {
        if ($paymentMethod !== CustomerPayment::METHOD_CASH || $amountReceivedRupiah === null) {
            return null;
        }

        return max($amountReceivedRupiah - $amountRupiah, 0);
    }
}
