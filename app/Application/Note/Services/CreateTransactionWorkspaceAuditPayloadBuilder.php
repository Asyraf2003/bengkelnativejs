<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;

final class CreateTransactionWorkspaceAuditPayloadBuilder
{
    /**
     * @param array{decision:string,amount_paid_rupiah:int,change_rupiah:int} $paymentSummary
     * @param list<array<string, mixed>> $packageAllocations
     * @return array<string, mixed>
     */
    public function build(Note $note, int $itemsCount, array $paymentSummary, array $packageAllocations = []): array
    {
        $payload = [
            'note_id' => $note->id(),
            'customer_name' => $note->customerName(),
            'items_count' => $itemsCount,
            'total_rupiah' => $note->totalRupiah()->amount(),
            'payment_decision' => $paymentSummary['decision'],
            'amount_paid_rupiah' => $paymentSummary['amount_paid_rupiah'],
        ];

        if ($packageAllocations !== []) {
            $payload['package_allocations'] = $packageAllocations;
        }

        return $payload;
    }
}
