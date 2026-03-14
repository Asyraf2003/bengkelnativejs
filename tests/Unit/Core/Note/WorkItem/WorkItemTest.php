<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Note\WorkItem;

use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class WorkItemTest extends TestCase
{
    public function test_it_creates_service_only_work_item_with_customer_owned_marker_and_correct_subtotal(): void
    {
        $workItem = WorkItem::createServiceOnly(
            'work-item-1',
            'note-1',
            1,
            ServiceDetail::create(
                'Servis Karburator',
                Money::fromInt(50000),
                ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            ),
        );

        $this->assertSame(WorkItem::TYPE_SERVICE_ONLY, $workItem->transactionType());
        $this->assertSame(WorkItem::STATUS_OPEN, $workItem->status());
        $this->assertSame(50000, $workItem->subtotalRupiah()->amount());
        $this->assertNotNull($workItem->serviceDetail());
        $this->assertSame(
            ServiceDetail::PART_SOURCE_CUSTOMER_OWNED,
            $workItem->serviceDetail()?->partSource(),
        );
        $this->assertCount(0, $workItem->externalPurchaseLines());
        $this->assertCount(0, $workItem->storeStockLines());
    }

    public function test_it_creates_service_with_external_purchase_and_calculates_subtotal_correctly(): void
    {
        $workItem = WorkItem::createServiceWithExternalPurchase(
            'work-item-1',
            'note-1',
            1,
            ServiceDetail::create(
                'Servis Mesin',
                Money::fromInt(70000),
                ServiceDetail::PART_SOURCE_NONE,
            ),
            [
                ExternalPurchaseLine::create(
                    'external-line-1',
                    'Busi beli luar',
                    Money::fromInt(15000),
                    2,
                ),
                ExternalPurchaseLine::create(
                    'external-line-2',
                    'Kabel gas beli luar',
                    Money::fromInt(10000),
                    1,
                ),
            ],
        );

        $this->assertSame(WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, $workItem->transactionType());
        $this->assertSame(WorkItem::STATUS_OPEN, $workItem->status());
        $this->assertSame(110000, $workItem->subtotalRupiah()->amount());
        $this->assertCount(2, $workItem->externalPurchaseLines());
        $this->assertCount(0, $workItem->storeStockLines());
    }

    public function test_it_marks_done_and_canceled(): void
    {
        $workItem = WorkItem::createStoreStockSaleOnly(
            'work-item-1',
            'note-1',
            1,
            [
                StoreStockLine::create(
                    'store-line-1',
                    'product-1',
                    2,
                    Money::fromInt(40000),
                ),
            ],
        );

        $this->assertSame(WorkItem::STATUS_OPEN, $workItem->status());

        $workItem->markDone();
        $this->assertSame(WorkItem::STATUS_DONE, $workItem->status());

        $workItem->cancel();
        $this->assertSame(WorkItem::STATUS_CANCELED, $workItem->status());
    }

    public function test_it_rejects_invalid_status_on_create(): void
    {
        $this->expectException(DomainException::class);

        WorkItem::createServiceOnly(
            'work-item-1',
            'note-1',
            1,
            ServiceDetail::create(
                'Servis Karburator',
                Money::fromInt(50000),
                ServiceDetail::PART_SOURCE_NONE,
            ),
            'archived',
        );
    }
}
