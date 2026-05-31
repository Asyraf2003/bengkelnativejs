<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropUniqueIfExists('products', 'products_kode_barang_active_unique');
        $this->dropUniqueIfExists('products', 'products_business_identity_active_unique');

        $this->createUniqueIfMissing(
            'products',
            ['kode_barang', 'active_unique_marker'],
            'products_kode_barang_unique'
        );

        $this->createUniqueIfMissing(
            'products',
            ['nama_barang_normalized', 'merek_normalized', 'ukuran', 'active_unique_marker'],
            'products_business_identity_unique'
        );
    }

    public function down(): void
    {
        $this->dropUniqueIfExists('products', 'products_kode_barang_unique');
        $this->dropUniqueIfExists('products', 'products_business_identity_unique');

        $this->createUniqueIfMissing(
            'products',
            ['kode_barang', 'active_unique_marker'],
            'products_kode_barang_active_unique'
        );

        $this->createUniqueIfMissing(
            'products',
            ['nama_barang_normalized', 'merek_normalized', 'ukuran', 'active_unique_marker'],
            'products_business_identity_active_unique'
        );
    }

    private function dropUniqueIfExists(string $table, string $index): void
    {
        if (! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropUnique($index);
        });
    }

    private function createUniqueIfMissing(string $table, array $columns, string $index): void
    {
        if ($this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->unique($columns, $index);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $key => $definition) {
            if (is_string($key) && $key === $index) {
                return true;
            }

            if (($definition['name'] ?? $definition['index_name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
};
