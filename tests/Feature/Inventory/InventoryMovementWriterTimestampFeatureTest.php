<?php

declare(strict_types=1);

use App\Adapters\Out\Inventory\DatabaseInventoryMovementWriterAdapter;
use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Shared\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('inventory movement writer stores operational timestamps on create many', function (): void {
    seedInventoryMovementTimestampProduct();

    $movement = InventoryMovement::create(
        'inventory-movement-writer-ts-1',
        'product-inventory-movement-ts-1',
        'stock_in',
        'supplier_receipt_line',
        'receipt-line-ts-1',
        new DateTimeImmutable('2026-05-15'),
        3,
        Money::fromInt(20_000),
    );

    (new DatabaseInventoryMovementWriterAdapter())->createMany([$movement]);

    $row = DB::table('inventory_movements')
        ->where('id', 'inventory-movement-writer-ts-1')
        ->first();

    expect($row)->not->toBeNull();

    $rowData = (array) $row;

    expect(array_key_exists('created_at', $rowData))
        ->toBeTrue('Missing inventory_movements.created_at on writer-created row');

    expect(array_key_exists('updated_at', $rowData))
        ->toBeTrue('Missing inventory_movements.updated_at on writer-created row');

    expect($rowData['created_at'])->not->toBeNull();
    expect($rowData['updated_at'])->not->toBeNull();

    expect((string) $row->product_id)->toBe('product-inventory-movement-ts-1');
    expect((string) $row->movement_type)->toBe('stock_in');
    expect((string) $row->source_type)->toBe('supplier_receipt_line');
    expect((string) $row->source_id)->toBe('receipt-line-ts-1');
    expect((string) $row->tanggal_mutasi)->toBe('2026-05-15');
    expect((int) $row->qty_delta)->toBe(3);
    expect((int) $row->unit_cost_rupiah)->toBe(20_000);
    expect((int) $row->total_cost_rupiah)->toBe(60_000);
});

function seedInventoryMovementTimestampProduct(): void
{
    DB::table('products')->insert([
        'id' => 'product-inventory-movement-ts-1',
        'kode_barang' => 'INV-TS-1',
        'nama_barang' => 'Inventory Timestamp Product',
        'merek' => 'Federal',
        'ukuran' => 1,
        'harga_jual' => 75_000,
    ]);
}
