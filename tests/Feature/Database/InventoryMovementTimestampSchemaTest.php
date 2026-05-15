<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has operational timestamps on inventory movement ledger table', function (): void {
    expect(Schema::hasColumn('inventory_movements', 'created_at'))
        ->toBeTrue('Missing inventory_movements.created_at');

    expect(Schema::hasColumn('inventory_movements', 'updated_at'))
        ->toBeTrue('Missing inventory_movements.updated_at');
});
