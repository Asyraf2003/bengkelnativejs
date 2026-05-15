<?php

declare(strict_types=1);

namespace App\Application\Reporting\DTO\Concerns;

trait TransactionCashLedgerPerNoteRowSourceAccessors
{
    public function sourceTable(): string
    {
        return $this->sourceTable;
    }

    public function sourceId(): string
    {
        return $this->sourceId;
    }

    public function sourceDispositionId(): ?string
    {
        return $this->sourceDispositionId;
    }
}
