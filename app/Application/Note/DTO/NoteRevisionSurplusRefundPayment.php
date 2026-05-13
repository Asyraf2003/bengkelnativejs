<?php

declare(strict_types=1);

namespace App\Application\Note\DTO;

use App\Core\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final class NoteRevisionSurplusRefundPayment
{
    public const STATUS_ACTIVE = 'active';

    private function __construct(
        public readonly string $id,
        public readonly string $noteRevisionSurplusDispositionId,
        public readonly string $noteRevisionSettlementId,
        public readonly string $noteRootId,
        public readonly string $noteRevisionId,
        public readonly int $amountRupiah,
        public readonly DateTimeImmutable $effectiveDate,
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $status,
        public readonly string $idempotencyKey,
        public readonly string $auditEventId,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        string $id,
        string $noteRevisionSurplusDispositionId,
        string $noteRevisionSettlementId,
        string $noteRootId,
        string $noteRevisionId,
        int $amountRupiah,
        DateTimeImmutable $effectiveDate,
        DateTimeImmutable $occurredAt,
        string $status,
        string $idempotencyKey,
        string $auditEventId,
        DateTimeImmutable $createdAt,
    ): self {
        $id = trim($id);
        $noteRevisionSurplusDispositionId = trim($noteRevisionSurplusDispositionId);
        $noteRevisionSettlementId = trim($noteRevisionSettlementId);
        $noteRootId = trim($noteRootId);
        $noteRevisionId = trim($noteRevisionId);
        $status = trim($status);
        $idempotencyKey = trim($idempotencyKey);
        $auditEventId = trim($auditEventId);

        if (
            $id === ''
            || $noteRevisionSurplusDispositionId === ''
            || $noteRevisionSettlementId === ''
            || $noteRootId === ''
            || $noteRevisionId === ''
            || $idempotencyKey === ''
            || $auditEventId === ''
        ) {
            throw new DomainException('Surplus refund payment identity wajib diisi.');
        }

        if ($amountRupiah <= 0) {
            throw new DomainException('Nominal surplus refund payment tidak valid.');
        }

        if ($status !== self::STATUS_ACTIVE) {
            throw new DomainException('Surplus refund payment status tidak didukung.');
        }

        return new self(
            $id,
            $noteRevisionSurplusDispositionId,
            $noteRevisionSettlementId,
            $noteRootId,
            $noteRevisionId,
            $amountRupiah,
            $effectiveDate,
            $occurredAt,
            $status,
            $idempotencyKey,
            $auditEventId,
            $createdAt,
        );
    }

    public function effectiveDateString(): string
    {
        return $this->effectiveDate->format('Y-m-d');
    }
}
