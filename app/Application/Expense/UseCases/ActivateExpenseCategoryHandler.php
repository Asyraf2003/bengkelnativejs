<?php

declare(strict_types=1);

namespace App\Application\Expense\UseCases;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Expense\ExpenseCategoryReaderPort;
use App\Ports\Out\Expense\ExpenseCategoryWriterPort;
use App\Ports\Out\UuidPort;

final class ActivateExpenseCategoryHandler
{
    public function __construct(
        private readonly ExpenseCategoryReaderPort $readers,
        private readonly ExpenseCategoryWriterPort $writers,
        private readonly AuditEventWriterPort $audit,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
    ) {
    }

    public function handle(string $categoryId, string $performedByActorId): Result
    {
        $category = $this->readers->findById($categoryId);

        if ($category === null) {
            return Result::failure('Expense category tidak ditemukan.', ['expense_category' => ['EXPENSE_CATEGORY_NOT_FOUND']]);
        }

        $before = $this->snapshot($category);

        $category->activate();

        $after = $this->snapshot($category);
        $actorId = trim($performedByActorId);

        $this->writers->update($category);
        $this->audit->write(new AuditEventWrite(
            id: $this->uuid->generate(),
            boundedContext: 'expense',
            aggregateType: 'expense_category',
            aggregateId: $category->id(),
            eventName: 'expense_category_activated',
            actorId: $actorId === '' ? null : $actorId,
            actorRole: null,
            reason: null,
            sourceChannel: null,
            requestId: null,
            correlationId: null,
            occurredAt: $this->clock->now(),
            metadata: [
                'category_id' => $category->id(),
                'performed_by_actor_id' => $actorId,
            ],
            snapshots: [
                new AuditEventSnapshotWrite('before', $before),
                new AuditEventSnapshotWrite('after', $after),
            ],
        ));

        return Result::success(['id' => $category->id(), 'is_active' => true], 'Expense category diaktifkan.');
    }

    private function snapshot(object $category): array
    {
        return [
            'id' => $category->id(),
            'code' => $category->code(),
            'name' => $category->name(),
            'description' => $category->description(),
            'is_active' => $category->isActive(),
        ];
    }
}
