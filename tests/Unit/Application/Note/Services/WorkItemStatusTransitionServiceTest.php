<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Note\Services;

use App\Application\Note\Services\WorkItemStatusTransitionService;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkItemStatusTransitionServiceTest extends TestCase
{
    public function test_canceled_work_item_cannot_transition_to_done(): void
    {
        $note = Note::rehydrate(
            'note-status-transition',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-02'),
            Money::fromInt(0),
            [
                WorkItem::createStoreStockSaleOnly('wi-canceled', 'note-status-transition', 1, [
                    StoreStockLine::create('sto-canceled', 'product-canceled', 1, Money::fromInt(7000)),
                ], WorkItem::STATUS_CANCELED),
            ],
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Work item CANCELED tidak dapat diubah ke status lain.');

        (new WorkItemStatusTransitionService())->findAndApplyById(
            $note,
            'wi-canceled',
            WorkItem::STATUS_DONE,
        );
    }
}
