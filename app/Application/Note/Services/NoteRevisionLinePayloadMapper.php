<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\ProductCatalog\ProductReaderPort;

final class NoteRevisionLinePayloadMapper
{
    public function __construct(
        private readonly ProductReaderPort $products,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function map(WorkItem $item): array
    {
        $payload = [
            'work_item_root_id' => $item->id(),
            'transaction_type' => $item->transactionType(),
            'status' => $item->status(),
            'external_purchase_lines' => array_map(
                static fn ($line): array => [
                    'id' => $line->id(),
                    'cost_description' => $line->costDescription(),
                    'unit_cost_rupiah' => $line->unitCostRupiah()->amount(),
                    'qty' => $line->qty(),
                    'line_total_rupiah' => $line->lineTotalRupiah()->amount(),
                ],
                $item->externalPurchaseLines(),
            ),
            'store_stock_lines' => array_map(
                fn (StoreStockLine $line): array => $this->mapStoreStockLine($line),
                $item->storeStockLines(),
            ),
        ];

        $service = $item->serviceDetail();

        if ($service !== null) {
            $payload['service'] = [
                'service_name' => $service->serviceName(),
                'service_price_rupiah' => $service->servicePriceRupiah()->amount(),
                'part_source' => $service->partSource(),
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapStoreStockLine(StoreStockLine $line): array
    {
        $productId = $line->productId();
        $product = $this->products->getById($productId);
        $productName = $product !== null ? trim($product->namaBarang()) : '';

        $payload = [
            'id' => $line->id(),
            'product_id' => $productId,
            'qty' => $line->qty(),
            'line_total_rupiah' => $line->lineTotalRupiah()->amount(),
        ];

        if ($productName !== '') {
            $payload['product_name_snapshot'] = $productName;
        }

        return $payload;
    }
}
