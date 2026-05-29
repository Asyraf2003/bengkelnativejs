<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class CreateTransactionWorkspacePersistResult
{
    /**
     * @param list<array<string, mixed>> $packageAllocations
     */
    public function __construct(
        private readonly int $itemsCount,
        private readonly array $packageAllocations,
    ) {
    }

    public function itemsCount(): int
    {
        return $this->itemsCount;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function packageAllocations(): array
    {
        return $this->packageAllocations;
    }
}
