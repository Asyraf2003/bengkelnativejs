<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;

final class WorkItemStatusTransitionService
{
    public function findAndApply(Note $note, int $lineNo, string $target): WorkItem
    {
        foreach ($note->workItems() as $item) {
            if ($item->lineNo() === $lineNo) {
                return $this->apply($item, $target);
            }
        }

        throw new DomainException('Work item pada note tidak ditemukan.');
    }

    public function findAndApplyById(Note $note, string $workItemId, string $target): WorkItem
    {
        $normalizedId = trim($workItemId);

        foreach ($note->workItems() as $item) {
            if ($item->id() === $normalizedId) {
                return $this->apply($item, $target);
            }
        }

        throw new DomainException('Work item pada note tidak ditemukan.');
    }

    private function apply(WorkItem $workItem, string $target): WorkItem
    {
        if ($workItem->status() === WorkItem::STATUS_CANCELED
            && $target !== WorkItem::STATUS_CANCELED) {
            throw new DomainException('Work item CANCELED tidak dapat diubah ke status lain.');
        }

        match ($target) {
            WorkItem::STATUS_DONE => $workItem->markDone(),
            WorkItem::STATUS_CANCELED => $workItem->cancel(),
            WorkItem::STATUS_OPEN => $this->ensureIsAlreadyOpen($workItem),
            default => throw new DomainException('Target status work item belum didukung.')
        };

        return $workItem;
    }

    private function ensureIsAlreadyOpen(WorkItem $item): void
    {
        if ($item->status() !== WorkItem::STATUS_OPEN) {
            throw new DomainException('Hanya work item OPEN yang bisa tetap di status OPEN.');
        }
    }
}
