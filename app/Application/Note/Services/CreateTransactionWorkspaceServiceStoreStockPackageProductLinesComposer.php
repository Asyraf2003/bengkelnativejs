<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ProductCatalog\ProductReaderPort;

final class CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposer
{
    public function __construct(
        private readonly ProductReaderPort $products,
    ) {
    }

    /**
     * @param mixed $value
     * @return array{product_lines:list<array<string, mixed>>,sparepart_total_rupiah:int}
     */
    public function compose(mixed $value): array
    {
        $lines = (new CreateTransactionWorkspaceProductLineCollection())->lines($value);

        if ($lines === []) {
            throw new DomainException('Product wajib dipilih.');
        }

        CreateTransactionWorkspaceDuplicateProductLineGuard::assertUnique($lines);

        $sparepartTotal = 0;
        $normalizedLines = [];

        foreach ($lines as $line) {
            [$normalizedLine, $lineTotal] = $this->composeLine($line);
            $sparepartTotal += $lineTotal;
            $normalizedLines[] = $normalizedLine;
        }

        return [
            'product_lines' => $normalizedLines,
            'sparepart_total_rupiah' => $sparepartTotal,
        ];
    }

    /**
     * @param array<string, mixed> $line
     * @return array{0:array<string, mixed>,1:int}
     */
    private function composeLine(array $line): array
    {
        $productId = $this->requiredString($line['product_id'] ?? null, 'Product wajib dipilih.');
        $qty = $this->requiredInt($line['qty'] ?? null, 'Qty produk wajib diisi.');

        $product = $this->products->getById($productId)
            ?? throw new DomainException('Product tidak ditemukan.');

        $productUnitPrice = $product->hargaJual()->amount();

        $line['product_id'] = $productId;
        $line['qty'] = $qty;
        $line['unit_price_rupiah'] = $productUnitPrice;

        return [$line, $productUnitPrice * $qty];
    }

    private function requiredString(mixed $value, string $message): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new DomainException($message);
        }

        return trim($value);
    }

    private function requiredInt(mixed $value, string $message): int
    {
        if (! is_int($value) || $value <= 0) {
            throw new DomainException($message);
        }

        return $value;
    }
}
