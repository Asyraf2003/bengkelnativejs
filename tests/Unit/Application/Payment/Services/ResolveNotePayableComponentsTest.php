<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Payment\Services;

use App\Application\Payment\Services\ResolveNotePayableComponents;
use App\Application\Payment\Services\ResolveNotePayableComponentsSelectedRows;
use App\Application\Payment\Services\ResolveNotePayableComponentsSelectionId;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ResolveNotePayableComponentsTest extends TestCase
{
    public function test_it_builds_components_for_mixed_note(): void
    {
        $note = Note::rehydrate(
            'note-1',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-02'),
            Money::fromInt(24000),
            [
                WorkItem::createStoreStockSaleOnly('wi-1', 'note-1', 1, [
                    StoreStockLine::create('sto-1', 'product-1', 1, Money::fromInt(5000)),
                ]),
                WorkItem::createServiceWithStoreStockPart(
                    'wi-2',
                    'note-1',
                    2,
                    ServiceDetail::create('Servis A', Money::fromInt(5000), ServiceDetail::PART_SOURCE_NONE),
                    [StoreStockLine::create('sto-2', 'product-2', 1, Money::fromInt(3000))]
                ),
                WorkItem::createServiceWithExternalPurchase(
                    'wi-3',
                    'note-1',
                    3,
                    ServiceDetail::create('Servis B', Money::fromInt(9000), ServiceDetail::PART_SOURCE_NONE),
                    [ExternalPurchaseLine::create('ext-1', 'Beli luar', Money::fromInt(2000), 1)]
                ),
            ],
        );

        $components = $this->makeService()->fromNote($note);

        $this->assertCount(5, $components);
        $this->assertSame(PaymentComponentType::PRODUCT_ONLY_WORK_ITEM, $components[0]->componentType());
        $this->assertSame(PaymentComponentType::SERVICE_STORE_STOCK_PART, $components[1]->componentType());
        $this->assertSame(PaymentComponentType::SERVICE_FEE, $components[2]->componentType());
        $this->assertSame(PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART, $components[3]->componentType());
        $this->assertSame(PaymentComponentType::SERVICE_FEE, $components[4]->componentType());
    }

    public function test_full_note_payable_components_skip_canceled_rows(): void
    {
        $note = $this->noteWithActiveAndCanceledRows();

        $components = $this->makeService()->fromNote($note);

        $this->assertCount(1, $components);
        $this->assertSame('wi-active', $components[0]->workItemId());
    }

    public function test_selected_canceled_row_is_rejected_as_invalid_for_payment_selection(): void
    {
        $note = $this->noteWithActiveAndCanceledRows();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Billing row pembayaran yang dipilih tidak valid untuk nota ini.');

        $this->makeService()->fromSelectedRows($note, ['wi-canceled']);
    }

    private function noteWithActiveAndCanceledRows(): Note
    {
        return Note::rehydrate(
            'note-canceled-row',
            'Budi',
            null,
            new DateTimeImmutable('2026-04-02'),
            Money::fromInt(5000),
            [
                WorkItem::createStoreStockSaleOnly('wi-active', 'note-canceled-row', 1, [
                    StoreStockLine::create('sto-active', 'product-active', 1, Money::fromInt(5000)),
                ]),
                WorkItem::createStoreStockSaleOnly('wi-canceled', 'note-canceled-row', 2, [
                    StoreStockLine::create('sto-canceled', 'product-canceled', 1, Money::fromInt(7000)),
                ], WorkItem::STATUS_CANCELED),
            ],
        );
    }

    private function makeService(): ResolveNotePayableComponents
    {
        return new ResolveNotePayableComponents(
            new ResolveNotePayableComponentsSelectedRows(
                new ResolveNotePayableComponentsSelectionId(),
            ),
        );
    }
}
