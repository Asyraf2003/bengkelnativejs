<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

final class Note
{
    /**
     * @param list<WorkItem> $workItems
     */
    private function __construct(
        private string $id,
        private string $customerName,
        private DateTimeImmutable $transactionDate,
        private array $workItems,
        private Money $totalRupiah,
    ) {
    }

    public static function create(
        string $id,
        string $customerName,
        DateTimeImmutable $transactionDate,
    ): self {
        self::assertValidIdentity($id, $customerName);

        return new self(
            trim($id),
            trim($customerName),
            $transactionDate,
            [],
            Money::zero(),
        );
    }

    /**
     * @param list<WorkItem> $workItems
     */
    public static function rehydrate(
        string $id,
        string $customerName,
        DateTimeImmutable $transactionDate,
        Money $totalRupiah,
        array $workItems = [],
    ): self {
        self::assertValidIdentity($id, $customerName);
        self::assertValidWorkItems($workItems);

        $totalRupiah->ensureNotNegative('Total rupiah note tidak boleh negatif.');

        if ($workItems !== []) {
            $calculatedTotal = self::calculateTotalFromWorkItems($workItems);

            if ($calculatedTotal->equals($totalRupiah) === false) {
                throw new DomainException('Total rupiah note tidak konsisten dengan subtotal work item.');
            }
        }

        return new self(
            trim($id),
            trim($customerName),
            $transactionDate,
            array_values($workItems),
            $totalRupiah,
        );
    }

    public function addWorkItem(WorkItem $workItem): void
    {
        if ($workItem->noteId() !== $this->id) {
            throw new DomainException('Work item tidak belong ke note ini.');
        }

        foreach ($this->workItems as $existingWorkItem) {
            if ($existingWorkItem->id() === $workItem->id()) {
                throw new DomainException('Work item id pada note tidak boleh duplikat.');
            }

            if ($existingWorkItem->lineNo() === $workItem->lineNo()) {
                throw new DomainException('Line number pada note tidak boleh duplikat.');
            }
        }

        $this->workItems[] = $workItem;
        $this->totalRupiah = $this->totalRupiah->add($workItem->subtotalRupiah());
    }

    public function syncTotalRupiah(Money $totalRupiah): void
    {
        $totalRupiah->ensureNotNegative('Total rupiah note tidak boleh negatif.');

        $this->totalRupiah = $totalRupiah;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function transactionDate(): DateTimeImmutable
    {
        return $this->transactionDate;
    }

    /**
     * @return list<WorkItem>
     */
    public function workItems(): array
    {
        return $this->workItems;
    }

    public function totalRupiah(): Money
    {
        return $this->totalRupiah;
    }

    private static function assertValidIdentity(
        string $id,
        string $customerName,
    ): void {
        if (trim($id) === '') {
            throw new DomainException('Note id wajib ada.');
        }

        if (trim($customerName) === '') {
            throw new DomainException('Customer name pada note wajib ada.');
        }
    }

    /**
     * @param list<WorkItem> $workItems
     */
    private static function assertValidWorkItems(array $workItems): void
    {
        foreach ($workItems as $workItem) {
            if ($workItem instanceof WorkItem === false) {
                throw new DomainException('Work item pada note tidak valid.');
            }
        }
    }

    /**
     * @param list<WorkItem> $workItems
     */
    private static function calculateTotalFromWorkItems(array $workItems): Money
    {
        $total = Money::zero();

        foreach ($workItems as $workItem) {
            $total = $total->add($workItem->subtotalRupiah());
        }

        return $total;
    }
}
