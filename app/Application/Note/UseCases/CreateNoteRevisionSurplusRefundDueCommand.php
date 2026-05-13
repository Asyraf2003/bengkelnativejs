<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use DateTimeImmutable;

final class CreateNoteRevisionSurplusRefundDueCommand
{
    public function __construct(
        public readonly string $noteRevisionSettlementId,
        public readonly int $amountRupiah,
        public readonly string $reason,
        public readonly string $actorId,
        public readonly string $actorRole,
        public readonly ?DateTimeImmutable $occurredAt = null,
        public readonly ?string $sourceChannel = null,
        public readonly ?string $requestId = null,
        public readonly ?string $correlationId = null,
    ) {
    }
}
