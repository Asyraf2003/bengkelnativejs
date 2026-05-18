<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class CreateTransactionWorkspacePersistResult
{
    /**
     * @param list<array{
     *     work_item_id:string,
     *     store_stock_line_id:string,
     *     pricing_mode:string,
     *     package_total_rupiah:int,
     *     sparepart_total_rupiah:int,
     *     service_price_rupiah:int,
     *     product_id:string,
     *     qty:int,
     *     product_unit_price_rupiah:int
     * }> $packageAllocations
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
     * @return list<array{
     *     work_item_id:string,
     *     store_stock_line_id:string,
     *     pricing_mode:string,
     *     package_total_rupiah:int,
     *     sparepart_total_rupiah:int,
     *     service_price_rupiah:int,
     *     product_id:string,
     *     qty:int,
     *     product_unit_price_rupiah:int
     * }>
     */
    public function packageAllocations(): array
    {
        return $this->packageAllocations;
    }
}
