<?php

declare(strict_types=1);

namespace App\Application\Note\Services\RevisionWorkspace;

use App\Core\Note\Revision\NoteRevisionLineSnapshot;

final class RevisionWorkspaceServiceStoreStockMapper
{
    public function __construct(
        private readonly RevisionWorkspaceProductLineMapper $products,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function map(NoteRevisionLineSnapshot $line): array
    {
        $payload = $line->payload();
        $service = is_array($payload['service'] ?? null) ? $payload['service'] : [];

        $productLines = array_map(
            fn (array $storeLine): array => $this->products->map($storeLine),
            $this->products->storeLines(
                $payload,
                'Revision servis + sparepart toko wajib memiliki minimal 1 store stock line.'
            )
        );

        return [
            'entry_mode' => 'service',
            'description' => '',
            'part_source' => 'store_stock',
            'pricing_mode' => $this->pricingMode($payload),
            'package_total_rupiah' => $this->packageTotal($line, $payload),
            'service' => [
                'name' => (string) ($service['service_name'] ?? ($line->serviceLabel() ?? '')),
                'price_rupiah' => (int) ($service['service_price_rupiah'] ?? ($line->servicePriceRupiah() ?? 0)),
                'notes' => '',
            ],
            'selected_label' => (string) ($productLines[0]['selected_label'] ?? ''),
            'product_lines' => $productLines,
            'external_purchase_lines' => [],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function pricingMode(array $payload): string
    {
        return ($payload['pricing_mode'] ?? null) === 'package_auto_split'
            ? 'package_auto_split'
            : 'package_auto_split';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function packageTotal(NoteRevisionLineSnapshot $line, array $payload): int
    {
        $payloadTotal = (int) ($payload['package_total_rupiah'] ?? 0);

        return $payloadTotal > 0 ? $payloadTotal : $line->subtotalRupiah();
    }
}
