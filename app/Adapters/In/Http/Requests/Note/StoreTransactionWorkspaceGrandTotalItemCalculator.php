<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

final class StoreTransactionWorkspaceGrandTotalItemCalculator
{
    /**
     * @param array<string, mixed> $item
     */
    public static function calculate(array $item): int
    {
        $entryMode = (string) ($item['entry_mode'] ?? '');
        $partSource = (string) ($item['part_source'] ?? 'none');

        if ($entryMode === 'product') {
            return StoreTransactionWorkspaceGrandTotalLineCalculator::productLineTotal($item['product_lines'] ?? []);
        }

        if ($entryMode !== 'service') {
            return 0;
        }

        if (self::usesPackageAutoSplit($item, $partSource)) {
            return StoreTransactionWorkspaceGrandTotalLineCalculator::intValue($item['package_total_rupiah'] ?? null);
        }

        $service = is_array($item['service'] ?? null) ? $item['service'] : [];
        $servicePrice = StoreTransactionWorkspaceGrandTotalLineCalculator::intValue($service['price_rupiah'] ?? null);

        if ($partSource === 'store_stock') {
            return $servicePrice
                + StoreTransactionWorkspaceGrandTotalLineCalculator::productLineTotal($item['product_lines'] ?? []);
        }

        if ($partSource === 'external_purchase') {
            return $servicePrice
                + StoreTransactionWorkspaceGrandTotalLineCalculator::externalLineTotal($item['external_purchase_lines'] ?? []);
        }

        return $servicePrice;
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function usesPackageAutoSplit(array $item, string $partSource): bool
    {
        if (! in_array($partSource, ['store_stock', 'external_purchase'], true)) {
            return false;
        }

        return ($item['pricing_mode'] ?? null) === 'package_auto_split'
            && StoreTransactionWorkspaceGrandTotalLineCalculator::intValue($item['package_total_rupiah'] ?? null) > 0;
    }
}
