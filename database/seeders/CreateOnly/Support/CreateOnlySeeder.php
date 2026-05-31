<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

abstract class CreateOnlySeeder extends Seeder
{
    protected function assertLocalOrTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(static::class.' is only allowed in local/testing environments.');
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function createOnly(string $table, string $key, mixed $value, array $row): bool
    {
        if (DB::table($table)->where($key, '=', $value)->exists()) {
            return false;
        }

        DB::table($table)->insert($this->filterExistingColumns($table, $row));

        return true;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function createOnlyReturningId(
        string $table,
        string $lookupKey,
        mixed $lookupValue,
        array $row,
        string $idColumn = 'id',
    ): int {
        $existing = DB::table($table)
            ->where($lookupKey, '=', $lookupValue)
            ->first([$idColumn]);

        if ($existing !== null) {
            return (int) $existing->{$idColumn};
        }

        return (int) DB::table($table)->insertGetId(
            $this->filterExistingColumns($table, $row),
            $idColumn
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function filterExistingColumns(string $table, array $row): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($row, $columns);
    }
}
