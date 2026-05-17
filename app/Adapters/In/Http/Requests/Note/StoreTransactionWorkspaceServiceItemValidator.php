<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Requests\Note;

use Illuminate\Validation\Validator;

final class StoreTransactionWorkspaceServiceItemValidator
{
    /**
     * @param array<string, mixed> $item
     */
    public static function validate(array $item, string $partSource, int $index, Validator $validator): void
    {
        $service = is_array($item['service'] ?? null) ? $item['service'] : [];

        if (self::blank($service['name'] ?? null)) {
            $validator->errors()->add("items.$index.service.name", 'Nama servis wajib diisi.');
        }

        if (! self::hasValidServicePrice($item)) {
            $validator->errors()->add("items.$index.service.price_rupiah", 'Harga servis wajib lebih dari 0 kecuali package service + sparepart.');
        }

        if (! in_array($partSource, ['none', 'store_stock', 'customer_owned', 'external_purchase'], true)) {
            $validator->errors()->add("items.$index.part_source", 'Sumber part servis tidak valid.');
            return;
        }

        if ($partSource === 'store_stock') {
            StoreTransactionWorkspaceProductItemValidator::validate($item, $index, $validator);
        }

        if ($partSource === 'external_purchase') {
            self::validateExternalLine($item, $index, $validator);
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function validateExternalLine(array $item, int $index, Validator $validator): void
    {
        $line = self::firstLine($item['external_purchase_lines'] ?? []);

        if (self::blank($line['label'] ?? null)) {
            $validator->errors()->add("items.$index.external_purchase_lines.0.label", 'Label pembelian luar wajib diisi.');
        }

        if (self::intValue($line['qty'] ?? null) <= 0) {
            $validator->errors()->add("items.$index.external_purchase_lines.0.qty", 'Qty pembelian luar wajib lebih dari 0.');
        }

        if (self::intValue($line['unit_cost_rupiah'] ?? null) <= 0) {
            $validator->errors()->add("items.$index.external_purchase_lines.0.unit_cost_rupiah", 'Biaya satuan pembelian luar wajib lebih dari 0.');
        }
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private static function firstLine(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $first = array_values($value)[0] ?? [];

        return is_array($first) ? $first : [];
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function hasValidServicePrice(array $item): bool
    {
        $service = is_array($item['service'] ?? null) ? $item['service'] : [];
        $price = self::intValue($service['price_rupiah'] ?? null);

        if ($price > 0) {
            return true;
        }

        return $price === 0
            && ($item['pricing_mode'] ?? null) === 'package_auto_split'
            && self::intValue($item['package_total_rupiah'] ?? null) > 0
            && self::hasStoreStockLine($item);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function hasStoreStockLine(array $item): bool
    {
        $line = self::firstLine($item['product_lines'] ?? []);

        return is_string($line['product_id'] ?? null)
            && trim((string) $line['product_id']) !== ''
            && self::intValue($line['qty'] ?? null) > 0
            && self::intValue($line['unit_price_rupiah'] ?? null) > 0;
    }

    private static function blank(mixed $value): bool
    {
        return ! is_string($value) || trim($value) === '';
    }

    private static function intValue(mixed $value): int
    {
        return is_int($value) ? $value : 0;
    }
}
