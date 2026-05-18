<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;
use App\Ports\Out\Note\WorkItemWriterPort;
use DateTimeImmutable;

final class UpdateTransactionWorkspaceWorkItemPersister
{
    public function __construct(
        private readonly ReverseIssuedInventoryByNoteService $reverseIssuedInventoryByNote,
        private readonly WorkItemWriterPort $workItems,
        private readonly CreateTransactionWorkspaceWorkItemPersister $createPersister,
    ) {
    }

    /**
     * @param mixed $items
     */
    public function persist(Note $note, mixed $items, DateTimeImmutable $date): int
    {
        $this->reverseIssuedInventoryByNote->execute($note, $date);
        $this->workItems->deleteByNoteId($note->id());
        $nextLineNo = $this->workItems->nextLineNoByNoteId($note->id());

        $note->replaceWorkItems([]);

        return $this->createPersister->persist($note, $items, $nextLineNo)->itemsCount();
    }
}
