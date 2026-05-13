<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

use App\Application\Note\DTO\NoteRevisionSurplusRefundDueSource;

interface NoteRevisionSurplusRefundDueSourceReaderPort
{
    public function findActiveRefundDueByDispositionIdForUpdate(
        string $dispositionId,
    ): ?NoteRevisionSurplusRefundDueSource;
}
