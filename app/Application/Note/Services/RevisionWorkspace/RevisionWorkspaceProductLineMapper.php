<?php

declare(strict_types=1);

namespace App\Application\Note\Services\RevisionWorkspace;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ProductCatalog\ProductReaderPort;

final class RevisionWorkspaceProductLineMapper
{
    public function __construct(
        private readonly ProductReaderPort $products,
    ) {
    }

    /**
     * @param array<string, mixed> $storeLine
     * @return array<string, mixed>
     */
    public function map(array $storeLine, int $fallbackSubtotal = 0): array
    {
        $qty = max((int) ($storeLine['qty'] ?? 1), 1);
        $lineTotal = (int) ($storeLine['line_total_rupiah'] ?? $storeLine['subtotal_rupiah'] ?? $fallbackSubtotal);
        $unitPrice = (int) ($storeLine['selling_price_rupiah'] ?? ($lineTotal > 0 ? intdiv($lineTotal, $qty) : 0));

        $productId = (string) ($storeLine['product_id'] ?? '');
        $selectedLabel = $this->selectedLabel($storeLine, $productId);

        return [
            'product_id' => $productId,
            'qty' => $qty,
            'unit_price_rupiah' => $unitPrice,
            'price_basis' => 'revision_snapshot',
            'selected_label' => $selectedLabel,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    public function storeLines(array $payload, string $message): array
    {
        $storeLines = is_array($payload['store_stock_lines'] ?? null)
            ? array_values(array_filter($payload['store_stock_lines'], 'is_array'))
            : [];

        if ($storeLines === []) {
            throw new DomainException($message);
        }

        return $storeLines;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function singleStoreLine(array $payload, string $message): array
    {
        $storeLines = $this->storeLines($payload, $message);

        if (count($storeLines) !== 1) {
            throw new DomainException($message);
        }

        return $storeLines[0];
    }

    /**
     * @param array<string, mixed> $storeLine
     */
    private function selectedLabel(array $storeLine, string $productId): string
    {
        foreach (['product_name_snapshot', 'product_nama_barang_snapshot'] as $snapshotKey) {
            $snapshotName = trim((string) ($storeLine[$snapshotKey] ?? ''));

            if ($snapshotName !== '') {
                return $snapshotName;
            }
        }

        if ($productId === '') {
            return '';
        }

        return $this->products->getById($productId)?->namaBarang() ?? '';
    }
}
