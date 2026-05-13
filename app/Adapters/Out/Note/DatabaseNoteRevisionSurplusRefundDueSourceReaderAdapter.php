<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\DTO\NoteRevisionSurplusRefundDueSource;
use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;
use App\Ports\Out\Note\NoteRevisionSurplusRefundDueSourceReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseNoteRevisionSurplusRefundDueSourceReaderAdapter implements
    NoteRevisionSurplusRefundDueSourceReaderPort
{
    public function findActiveRefundDueByDispositionIdForUpdate(
        string $dispositionId,
    ): ?NoteRevisionSurplusRefundDueSource {
        $dispositionId = trim($dispositionId);

        if ($dispositionId === '') {
            return null;
        }

        $disposition = DB::table('note_revision_surplus_dispositions')
            ->where('id', $dispositionId)
            ->where('disposition_type', NoteRevisionSurplusDisposition::TYPE_REFUND_DUE)
            ->where('status', NoteRevisionSurplusDisposition::STATUS_ACTIVE)
            ->lockForUpdate()
            ->first();

        if ($disposition === null) {
            return null;
        }

        $activeRefundPaidRupiah = (int) DB::table('note_revision_surplus_refund_payments')
            ->where('note_revision_surplus_disposition_id', $dispositionId)
            ->where('status', NoteRevisionSurplusRefundPayment::STATUS_ACTIVE)
            ->sum('amount_rupiah');

        return NoteRevisionSurplusRefundDueSource::create(
            (string) $disposition->id,
            (string) $disposition->note_revision_settlement_id,
            (string) $disposition->note_root_id,
            (string) $disposition->note_revision_id,
            (string) $disposition->disposition_type,
            (int) $disposition->amount_rupiah,
            (string) $disposition->status,
            $activeRefundPaidRupiah,
        );
    }
}
