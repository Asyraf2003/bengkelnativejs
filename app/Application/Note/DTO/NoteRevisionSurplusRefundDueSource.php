<?php

declare(strict_types=1);

namespace App\Application\Note\DTO;

use App\Core\Shared\Exceptions\DomainException;

final class NoteRevisionSurplusRefundDueSource
{
    private function __construct(
        public readonly string $dispositionId,
        public readonly string $noteRevisionSettlementId,
        public readonly string $noteRootId,
        public readonly string $noteRevisionId,
        public readonly int $refundDueRupiah,
        public readonly int $activeRefundPaidRupiah,
        public readonly int $remainingRefundDueRupiah,
    ) {
    }

    public static function create(
        string $dispositionId,
        string $noteRevisionSettlementId,
        string $noteRootId,
        string $noteRevisionId,
        string $dispositionType,
        int $refundDueRupiah,
        string $status,
        int $activeRefundPaidRupiah,
    ): self {
        $dispositionId = trim($dispositionId);
        $noteRevisionSettlementId = trim($noteRevisionSettlementId);
        $noteRootId = trim($noteRootId);
        $noteRevisionId = trim($noteRevisionId);
        $dispositionType = trim($dispositionType);
        $status = trim($status);

        if ($dispositionId === '' || $noteRevisionSettlementId === '' || $noteRootId === '' || $noteRevisionId === '') {
            throw new DomainException('Source refund_due identity wajib diisi.');
        }

        if ($dispositionType !== NoteRevisionSurplusDisposition::TYPE_REFUND_DUE) {
            throw new DomainException('Source refund_due type tidak valid.');
        }

        if ($status !== NoteRevisionSurplusDisposition::STATUS_ACTIVE) {
            throw new DomainException('Source refund_due status tidak valid.');
        }

        if ($refundDueRupiah <= 0 || $activeRefundPaidRupiah < 0 || $activeRefundPaidRupiah > $refundDueRupiah) {
            throw new DomainException('Source refund_due amount tidak valid.');
        }

        return new self(
            $dispositionId,
            $noteRevisionSettlementId,
            $noteRootId,
            $noteRevisionId,
            $refundDueRupiah,
            $activeRefundPaidRupiah,
            $refundDueRupiah - $activeRefundPaidRupiah,
        );
    }
}
