<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;

interface NoteRevisionSurplusRefundPaymentReaderPort
{
    public function findActiveByDispositionIdAndIdempotencyKey(
        string $dispositionId,
        string $idempotencyKey,
    ): ?NoteRevisionSurplusRefundPayment;

    public function sumActiveAmountByDispositionId(string $dispositionId): int;
}
