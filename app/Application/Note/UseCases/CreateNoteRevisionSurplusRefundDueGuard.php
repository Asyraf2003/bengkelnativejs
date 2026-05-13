<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\DTO\NoteRevisionSurplusPending;
use App\Core\Shared\Exceptions\DomainException;

final class CreateNoteRevisionSurplusRefundDueGuard
{
    public function assertCommandAllowed(CreateNoteRevisionSurplusRefundDueCommand $command): string
    {
        if (trim($command->actorId) === '' || trim($command->actorRole) !== 'admin') {
            throw new DomainException('Surplus refund_due hanya boleh dibuat oleh admin.');
        }

        $reason = trim($command->reason);

        if ($reason === '') {
            throw new DomainException('Alasan refund_due wajib diisi.');
        }

        if ($command->amountRupiah <= 0) {
            throw new DomainException('Nominal refund_due tidak valid.');
        }

        return $reason;
    }

    public function pendingOrFail(?NoteRevisionSurplusPending $pending): NoteRevisionSurplusPending
    {
        if ($pending === null || $pending->unresolvedPendingRupiah <= 0) {
            throw new DomainException('Pending surplus settlement tidak valid atau sudah selesai.');
        }

        return $pending;
    }

    public function assertAmountFits(
        int $amountRupiah,
        NoteRevisionSurplusPending $pending,
    ): void {
        if ($amountRupiah > $pending->unresolvedPendingRupiah) {
            throw new DomainException('Nominal refund_due melebihi pending surplus.');
        }
    }
}
