<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Support\Facades\DB;

trait NoteDetailOperationalPackageFixture
{
    use NoteDetailOperationalPackagePayloadFixture;

    private function seedVisibleStoreStockPackageDetailFixture(string $today): void
    {
        $this->seedDetailPackageProducts();
        $this->seedNoteBase('note-detail-package-1', 'Pelanggan Detail', $today, 250000);

        DB::table('notes')->where('id', 'note-detail-package-1')->update([
            'operational_note' => 'Keterangan operasional detail package',
        ]);

        $this->seedWorkItemBase(
            'wi-detail-package-1',
            'note-detail-package-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            250000,
        );

        $this->seedServiceDetailBase(
            'wi-detail-package-1',
            'Service Paket Detail',
            120000,
            'store_stock',
        );

        $this->seedStoreStockLineBase('sto-detail-a', 'wi-detail-package-1', 'product-detail-a', 2, 100000);
        $this->seedStoreStockLineBase('sto-detail-b', 'wi-detail-package-1', 'product-detail-b', 1, 30000);
        $this->seedDetailPackageCurrentRevision($today);
    }

    private function seedDetailPackageProducts(): void
    {
        $this->seedNotePaymentProduct(
            'product-detail-a',
            'FILTER-DETAIL-A',
            'Filter Oli Detail',
            'Yamaha',
            100,
            50000,
        );

        $this->seedNotePaymentProduct(
            'product-detail-b',
            'BUSI-DETAIL-B',
            'Busi Iridium Detail',
            'NGK',
            1,
            30000,
        );
    }

    private function seedDetailPackageCurrentRevision(string $today): void
    {
        $this->seedCurrentRevision(
            'note-detail-package-1',
            'rev-detail-package-1',
            'Pelanggan Detail',
            null,
            $today,
            250000,
            [$this->detailPackageRevisionLine()],
        );
    }
}
