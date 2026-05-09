<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Payment\DTO\PayableNoteComponent;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;

final class ResolveNotePayableComponentsSelectedRows
{
    public function __construct(
        private readonly ResolveNotePayableComponentsSelectionId $ids,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     * @return list<PayableNoteComponent>
     */
    public function resolve(Note $note, array $selectedRowIds): array
    {
        $selectedIds = $this->ids->normalize($selectedRowIds);

        if ($selectedIds === []) {
            throw new DomainException('Pilih minimal satu billing row outstanding untuk pembayaran.');
        }

        $components = [];
        $nextOrder = 1;

        foreach ($note->workItems() as $item) {
            if ($item->status() === WorkItem::STATUS_CANCELED) {
                continue;
            }

            $resolved = PayableComponentsFromWorkItem::resolve($item, $nextOrder);

            foreach ($resolved as $component) {
                $componentId = $this->ids->fromComponent($component);

                if (in_array($componentId, $selectedIds, true)
                    || in_array($component->workItemId(), $selectedIds, true)) {
                    $components[] = $component;
                }
            }

            $nextOrder += count($resolved);
        }

        $matchedIds = [];
        foreach ($components as $component) {
            $matchedIds[] = $this->ids->fromComponent($component);
            $matchedIds[] = $component->workItemId();
        }

        $matchedIds = array_values(array_unique($matchedIds));

        if (array_values(array_diff($selectedIds, $matchedIds)) !== []) {
            throw new DomainException('Billing row pembayaran yang dipilih tidak valid untuk nota ini.');
        }

        if ($components === []) {
            throw new DomainException('Billing row pembayaran yang dipilih tidak memiliki komponen yang bisa dibayar.');
        }

        return $components;
    }
}
