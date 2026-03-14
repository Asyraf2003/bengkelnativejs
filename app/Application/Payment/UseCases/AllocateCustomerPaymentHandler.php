<?php

declare(strict_types=1);

namespace App\Application\Payment\UseCases;

use App\Application\Shared\DTO\Result;
use App\Core\Payment\PaymentAllocation\PaymentAllocation;
use App\Core\Payment\Policies\PaymentAllocationPolicy;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\CustomerPaymentReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentAllocationWriterPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Throwable;

final class AllocateCustomerPaymentHandler
{
    public function __construct(
        private readonly CustomerPaymentReaderPort $customerPayments,
        private readonly PaymentAllocationReaderPort $paymentAllocations,
        private readonly PaymentAllocationWriterPort $paymentAllocationWriter,
        private readonly NoteReaderPort $notes,
        private readonly PaymentAllocationPolicy $policy,
        private readonly TransactionManagerPort $transactions,
        private readonly UuidPort $uuid,
    ) {
    }

    public function handle(
        string $customerPaymentId,
        string $noteId,
        int $amountRupiah,
    ): Result {
        try {
            $normalizedCustomerPaymentId = $this->normalizeRequired(
                $customerPaymentId,
                'Customer payment id pada allocation wajib ada.'
            );

            $normalizedNoteId = $this->normalizeRequired(
                $noteId,
                'Note id pada allocation wajib ada.'
            );

            $allocationAmountRupiah = Money::fromInt($amountRupiah);
        } catch (DomainException $e) {
            return $this->failureFromDomainException($e);
        }

        $transactionStarted = false;

        try {
            $this->transactions->begin();
            $transactionStarted = true;

            $customerPayment = $this->customerPayments->getById($normalizedCustomerPaymentId);
            $note = $this->notes->getById($normalizedNoteId);

            if ($customerPayment === null || $note === null) {
                throw new DomainException('Target payment allocation tidak ditemukan.');
            }

            $totalAllocatedByPaymentRupiah = $this->customerPayments
                ->getTotalAllocatedAmountByPaymentId($customerPayment->id());

            $totalAllocatedByNoteRupiah = $this->paymentAllocations
                ->getTotalAllocatedAmountByNoteId($note->id());

            $this->policy->assertAllocatable(
                $allocationAmountRupiah,
                $customerPayment->amountRupiah(),
                $totalAllocatedByPaymentRupiah,
                $note->totalRupiah(),
                $totalAllocatedByNoteRupiah,
            );

            $paymentAllocation = PaymentAllocation::create(
                $this->uuid->generate(),
                $customerPayment->id(),
                $note->id(),
                $allocationAmountRupiah,
            );

            $this->paymentAllocationWriter->create($paymentAllocation);

            $updatedAllocatedByPaymentRupiah = $totalAllocatedByPaymentRupiah->add(
                $paymentAllocation->amountRupiah()
            );

            $updatedAllocatedByNoteRupiah = $totalAllocatedByNoteRupiah->add(
                $paymentAllocation->amountRupiah()
            );

            $remainingPaymentRupiah = $customerPayment->amountRupiah()->subtract(
                $updatedAllocatedByPaymentRupiah
            );
            $remainingPaymentRupiah->ensureNotNegative('Total alokasi pada customer payment melebihi amount payment.');

            $outstandingRupiah = $note->totalRupiah()->subtract($updatedAllocatedByNoteRupiah);
            $outstandingRupiah->ensureNotNegative('Total alokasi pada note melebihi total note.');

            $this->transactions->commit();

            return Result::success(
                [
                    'payment_allocation' => [
                        'id' => $paymentAllocation->id(),
                        'customer_payment_id' => $paymentAllocation->customerPaymentId(),
                        'note_id' => $paymentAllocation->noteId(),
                        'amount_rupiah' => $paymentAllocation->amountRupiah()->amount(),
                    ],
                    'payment' => [
                        'id' => $customerPayment->id(),
                        'amount_rupiah' => $customerPayment->amountRupiah()->amount(),
                        'total_allocated_rupiah' => $updatedAllocatedByPaymentRupiah->amount(),
                        'remaining_allocatable_rupiah' => $remainingPaymentRupiah->amount(),
                    ],
                    'note_receivable' => [
                        'note_id' => $note->id(),
                        'total_rupiah' => $note->totalRupiah()->amount(),
                        'total_allocated_rupiah' => $updatedAllocatedByNoteRupiah->amount(),
                        'outstanding_rupiah' => $outstandingRupiah->amount(),
                        'is_paid' => $outstandingRupiah->isZero(),
                    ],
                ],
                'Payment allocation berhasil dicatat.'
            );
        } catch (DomainException $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            return $this->failureFromDomainException($e);
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }

    private function failureFromDomainException(DomainException $e): Result
    {
        return Result::failure(
            $e->getMessage(),
            ['payment' => [$this->classifyErrorCode($e->getMessage())]]
        );
    }

    private function classifyErrorCode(string $message): string
    {
        if (str_contains($message, 'Target payment allocation tidak ditemukan')) {
            return 'PAYMENT_INVALID_TARGET';
        }

        if (str_contains($message, 'melebihi sisa payment yang tersedia')) {
            return 'PAYMENT_OVER_ALLOCATION';
        }

        if (str_contains($message, 'melebihi outstanding note')) {
            return 'PAYMENT_EXCEEDS_OUTSTANDING';
        }

        return 'INVALID_PAYMENT_ALLOCATION';
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
