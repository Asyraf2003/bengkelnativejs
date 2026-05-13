<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Application\Note\DTO\NoteRevisionSurplusRefundPayment;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentWriterPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use stdClass;

final class DatabaseNoteRevisionSurplusRefundPaymentAdapter implements
    NoteRevisionSurplusRefundPaymentReaderPort,
    NoteRevisionSurplusRefundPaymentWriterPort
{
    public function create(NoteRevisionSurplusRefundPayment $payment): void
    {
        DB::table('note_revision_surplus_refund_payments')->insert([
            'id' => $payment->id,
            'note_revision_surplus_disposition_id' => $payment->noteRevisionSurplusDispositionId,
            'note_revision_settlement_id' => $payment->noteRevisionSettlementId,
            'note_root_id' => $payment->noteRootId,
            'note_revision_id' => $payment->noteRevisionId,
            'amount_rupiah' => $payment->amountRupiah,
            'effective_date' => $payment->effectiveDateString(),
            'occurred_at' => $payment->occurredAt->format('Y-m-d H:i:s'),
            'status' => $payment->status,
            'idempotency_key' => $payment->idempotencyKey,
            'audit_event_id' => $payment->auditEventId,
            'created_at' => $payment->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => null,
        ]);
    }

    public function findActiveByDispositionIdAndIdempotencyKey(
        string $dispositionId,
        string $idempotencyKey,
    ): ?NoteRevisionSurplusRefundPayment {
        $dispositionId = trim($dispositionId);
        $idempotencyKey = trim($idempotencyKey);

        if ($dispositionId === '' || $idempotencyKey === '') {
            return null;
        }

        $row = DB::table('note_revision_surplus_refund_payments')
            ->where('note_revision_surplus_disposition_id', $dispositionId)
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', NoteRevisionSurplusRefundPayment::STATUS_ACTIVE)
            ->first();

        return $row === null ? null : $this->mapRow($row);
    }

    public function sumActiveAmountByDispositionId(string $dispositionId): int
    {
        $dispositionId = trim($dispositionId);

        if ($dispositionId === '') {
            return 0;
        }

        return (int) DB::table('note_revision_surplus_refund_payments')
            ->where('note_revision_surplus_disposition_id', $dispositionId)
            ->where('status', NoteRevisionSurplusRefundPayment::STATUS_ACTIVE)
            ->sum('amount_rupiah');
    }

    private function mapRow(stdClass $row): NoteRevisionSurplusRefundPayment
    {
        return NoteRevisionSurplusRefundPayment::create(
            (string) $row->id,
            (string) $row->note_revision_surplus_disposition_id,
            (string) $row->note_revision_settlement_id,
            (string) $row->note_root_id,
            (string) $row->note_revision_id,
            (int) $row->amount_rupiah,
            new DateTimeImmutable((string) $row->effective_date),
            new DateTimeImmutable((string) $row->occurred_at),
            (string) $row->status,
            (string) $row->idempotency_key,
            (string) $row->audit_event_id,
            new DateTimeImmutable((string) $row->created_at),
        );
    }
}
