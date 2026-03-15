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
        $workItem = null;
        foreach ($note->workItems() as $item) {
            if ($item->lineNo() === $lineNo) {
                $workItem = $item;
                break;
            }
        }

        if ($workItem === null) {
            throw new DomainException('Work item pada note tidak ditemukan.');
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
