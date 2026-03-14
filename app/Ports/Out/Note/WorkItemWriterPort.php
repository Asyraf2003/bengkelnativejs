<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

use App\Core\Note\WorkItem\WorkItem;

interface WorkItemWriterPort
{
    public function create(WorkItem $workItem): void;

    public function updateStatus(WorkItem $workItem): void;
}
