<?php

declare(strict_types=1);

namespace App\Application\MobileApi\Product\UseCases;

use App\Application\Note\Services\CashierNoteProductLookupData;

final readonly class SearchMobileApiProductsHandler
{
    private const MIN_QUERY_LENGTH = 2;
    private const DEFAULT_LIMIT = 20;

    public function __construct(private CashierNoteProductLookupData $lookupData)
    {
    }

    /**
     * @return array{rows:list<array{id:string,label:string,kode_barang:?string,nama_barang:string,merek:string,ukuran:?int,available_stock:int,default_unit_price_rupiah:int,minimum_unit_price_rupiah:int}>,meta:array{query:string,limit:int}}
     */
    public function handle(string $query): array
    {
        $normalizedQuery = trim($query);
        $limit = self::DEFAULT_LIMIT;

        if (mb_strlen($normalizedQuery) < self::MIN_QUERY_LENGTH) {
            return [
                'rows' => [],
                'meta' => [
                    'query' => $normalizedQuery,
                    'limit' => $limit,
                ],
            ];
        }

        $rows = [];

        foreach (array_slice($this->lookupData->searchProducts($normalizedQuery), 0, $limit) as $product) {
            $inventory = $this->lookupData->getInventoryByProductId($product->id());
            $availableStock = $inventory?->qtyOnHand() ?? 0;
            $floorPrice = $product->hargaJual()->amount();

            $parts = [
                $product->namaBarang(),
                $product->merek(),
            ];

            if ($product->ukuran() !== null) {
                $parts[] = (string) $product->ukuran();
            }

            $label = implode(' — ', $parts);

            if ($product->kodeBarang() !== null) {
                $label .= ' (' . $product->kodeBarang() . ')';
            }

            $rows[] = [
                'id' => $product->id(),
                'label' => $label,
                'kode_barang' => $product->kodeBarang(),
                'nama_barang' => $product->namaBarang(),
                'merek' => $product->merek(),
                'ukuran' => $product->ukuran(),
                'available_stock' => $availableStock,
                'default_unit_price_rupiah' => $floorPrice,
                'minimum_unit_price_rupiah' => $floorPrice,
            ];
        }

        return [
            'rows' => $rows,
            'meta' => [
                'query' => $normalizedQuery,
                'limit' => $limit,
            ],
        ];
    }
}
