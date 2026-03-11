<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateSupplierInvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_supplier_invoice_endpoint_stores_invoice_with_existing_product_and_creates_supplier_when_missing(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        DB::table('products')->insert([
            'id' => 'product-2',
            'kode_barang' => 'KB-002',
            'nama_barang' => 'Vario',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 17000,
        ]);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nama_pt_pengirim' => '  PT Sumber Makmur  ',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => [
                [
                    'product_id' => 'product-1',
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
                [
                    'product_id' => 'product-2',
                    'qty_pcs' => 3,
                    'line_total_rupiah' => 30000,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('suppliers', [
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'nama_pt_pengirim_normalized' => 'pt sumber makmur',
        ]);

        $supplier = DB::table('suppliers')
            ->where('nama_pt_pengirim_normalized', 'pt sumber makmur')
            ->first();

        $this->assertNotNull($supplier);

        $this->assertDatabaseHas('supplier_invoices', [
            'supplier_id' => (string) $supplier->id,
            'tanggal_pengiriman' => '2026-03-12',
            'jatuh_tempo' => '2026-04-12',
            'grand_total_rupiah' => 50000,
        ]);

        $invoice = DB::table('supplier_invoices')
            ->where('supplier_id', (string) $supplier->id)
            ->first();

        $this->assertNotNull($invoice);

        $this->assertDatabaseHas('supplier_invoice_lines', [
            'supplier_invoice_id' => (string) $invoice->id,
            'product_id' => 'product-1',
            'qty_pcs' => 2,
            'line_total_rupiah' => 20000,
            'unit_cost_rupiah' => 10000,
        ]);

        $this->assertDatabaseHas('supplier_invoice_lines', [
            'supplier_invoice_id' => (string) $invoice->id,
            'product_id' => 'product-2',
            'qty_pcs' => 3,
            'line_total_rupiah' => 30000,
            'unit_cost_rupiah' => 10000,
        ]);
    }

    public function test_create_supplier_invoice_endpoint_rejects_unknown_product(): void
    {
        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => [
                [
                    'product_id' => 'unknown-product',
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('suppliers', 0);
        $this->assertDatabaseCount('supplier_invoices', 0);
        $this->assertDatabaseCount('supplier_invoice_lines', 0);
    }

    public function test_create_supplier_invoice_endpoint_rejects_line_total_that_is_not_evenly_divisible_by_qty(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'tanggal_pengiriman' => '2026-03-12',
            'lines' => [
                [
                    'product_id' => 'product-1',
                    'qty_pcs' => 3,
                    'line_total_rupiah' => 10000,
                ],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('suppliers', 0);
        $this->assertDatabaseCount('supplier_invoices', 0);
        $this->assertDatabaseCount('supplier_invoice_lines', 0);
    }

    public function test_create_supplier_invoice_endpoint_reuses_existing_supplier_with_same_normalized_name(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        DB::table('suppliers')->insert([
            'id' => 'supplier-1',
            'nama_pt_pengirim' => 'PT Sumber Makmur',
            'nama_pt_pengirim_normalized' => 'pt sumber makmur',
        ]);

        $response = $this->postJson('/procurement/supplier-invoices/create', [
            'nama_pt_pengirim' => '  pt   sumber    makmur ',
            'tanggal_pengiriman' => '2026-01-30',
            'lines' => [
                [
                    'product_id' => 'product-1',
                    'qty_pcs' => 2,
                    'line_total_rupiah' => 20000,
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('suppliers', 1);

        $this->assertDatabaseHas('supplier_invoices', [
            'supplier_id' => 'supplier-1',
            'tanggal_pengiriman' => '2026-01-30',
            'jatuh_tempo' => '2026-02-28',
            'grand_total_rupiah' => 20000,
        ]);
    }
}
