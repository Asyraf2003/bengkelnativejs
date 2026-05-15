<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has operational timestamps on allocation persistence tables', function (): void {
    $expectedColumns = [
        'payment_allocations' => [
            'created_at',
            'updated_at',
        ],
        'payment_component_allocations' => [
            'created_at',
            'updated_at',
        ],
        'refund_component_allocations' => [
            'created_at',
            'updated_at',
        ],
    ];

    $missingColumns = [];

    foreach ($expectedColumns as $table => $columns) {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                $missingColumns[] = "{$table}.{$column}";
            }
        }
    }

    expect($missingColumns)->toBe([]);
});
