<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createUniqueIfMissing(
            'supplier_invoice_lines',
            ['supplier_invoice_id', 'revision_no', 'product_id'],
            'sil_supplier_invoice_revision_product_unique'
        );
    }

    public function down(): void
    {
        $this->dropUniqueIfExists(
            'supplier_invoice_lines',
            'sil_supplier_invoice_revision_product_unique'
        );
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

    private function dropUniqueIfExists(string $table, string $index): void
    {
        if (! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropUnique($index);
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
