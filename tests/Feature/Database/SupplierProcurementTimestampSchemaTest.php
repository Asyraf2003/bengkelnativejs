<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('has operational timestamps on supplier procurement root tables', function (): void {
    $columns = [
        'supplier_invoices.created_at' => ['supplier_invoices', 'created_at'],
        'supplier_invoices.updated_at' => ['supplier_invoices', 'updated_at'],
        'supplier_receipts.created_at' => ['supplier_receipts', 'created_at'],
        'supplier_receipts.updated_at' => ['supplier_receipts', 'updated_at'],
        'supplier_payments.created_at' => ['supplier_payments', 'created_at'],
        'supplier_payments.updated_at' => ['supplier_payments', 'updated_at'],
    ];

    foreach ($columns as $label => [$table, $column]) {
        expect(Schema::hasColumn($table, $column))
            ->withContext($label)
            ->toBeTrue();
    }
});
