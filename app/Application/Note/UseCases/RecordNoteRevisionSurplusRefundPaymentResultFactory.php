<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;

final class RecordNoteRevisionSurplusRefundPaymentResultFactory
{
    public function success(
        NoteRevisionSurplusRefundPayment $payment,
        int $remainingRefundDueRupiah,
    ): RecordNoteRevisionSurplusRefundPaymentResult {
        return RecordNoteRevisionSurplusRefundPaymentResult::success([
            'refund_payment_id' => $payment->id,
            'note_revision_surplus_disposition_id' => $payment->noteRevisionSurplusDispositionId,
            'note_revision_settlement_id' => $payment->noteRevisionSettlementId,
            'note_root_id' => $payment->noteRootId,
            'note_revision_id' => $payment->noteRevisionId,
            'amount_rupiah' => $payment->amountRupiah,
            'effective_date' => $payment->effectiveDateString(),
            'occurred_at' => $payment->occurredAt->format('Y-m-d H:i:s'),
            'status' => $payment->status,
            'remaining_refund_due_rupiah' => $remainingRefundDueRupiah,
            'audit_event_id' => $payment->auditEventId,
        ]);
    }
}
