<?php

declare(strict_types=1);

namespace App\Core\Payment\Policies;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class PaymentAllocationPolicy
{
    public function assertAllocatable(
        Money $allocationAmountRupiah,
        Money $paymentAmountRupiah,
        Money $totalAllocatedByPaymentRupiah,
        Money $noteTotalRupiah,
        Money $totalAllocatedByNoteRupiah,
    ): void {
        if ($allocationAmountRupiah->greaterThan(Money::zero()) === false) {
            throw new DomainException('Amount alokasi payment harus lebih besar dari nol.');
        }

        if ($paymentAmountRupiah->greaterThan(Money::zero()) === false) {
            throw new DomainException('Amount customer payment harus lebih besar dari nol.');
        }

        $noteTotalRupiah->ensureNotNegative('Total rupiah note tidak boleh negatif.');
        $totalAllocatedByPaymentRupiah->ensureNotNegative('Total alokasi pada customer payment tidak boleh negatif.');
        $totalAllocatedByNoteRupiah->ensureNotNegative('Total alokasi pada note tidak boleh negatif.');

        $remainingPaymentRupiah = $paymentAmountRupiah->subtract($totalAllocatedByPaymentRupiah);
        $remainingPaymentRupiah->ensureNotNegative('Total alokasi pada customer payment melebihi amount payment.');

        $outstandingNoteRupiah = $noteTotalRupiah->subtract($totalAllocatedByNoteRupiah);
        $outstandingNoteRupiah->ensureNotNegative('Total alokasi pada note melebihi total note.');

        if ($allocationAmountRupiah->greaterThan($remainingPaymentRupiah)) {
            throw new DomainException('Amount alokasi payment melebihi sisa payment yang tersedia.');
        }

        if ($allocationAmountRupiah->greaterThan($outstandingNoteRupiah)) {
            throw new DomainException('Amount alokasi payment melebihi outstanding note.');
        }
    }
}
