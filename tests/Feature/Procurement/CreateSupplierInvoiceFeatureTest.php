<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateSupplierInvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_supplier_invoice_endpoint_auto_receives_by_default_and_updates_inventory(): void
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

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_invoice_id' => (string) $invoice->id,
            'amount_rupiah' => 50000,
            'paid_at' => '2026-03-12',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);

        $invoiceLine1 = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->where('product_id', 'product-1')
            ->first();

        $invoiceLine2 = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->where('product_id', 'product-2')
            ->first();

        $this->assertNotNull($invoiceLine1);
        $this->assertNotNull($invoiceLine2);

        $this->assertDatabaseHas('supplier_receipts', [
            'supplier_invoice_id' => (string) $invoice->id,
            'tanggal_terima' => '2026-03-12',
        ]);

        $receipt = DB::table('supplier_receipts')
            ->where('supplier_invoice_id', (string) $invoice->id)
            ->first();

        $this->assertNotNull($receipt);

        $this->assertDatabaseHas('supplier_receipt_lines', [
            'supplier_receipt_id' => (string) $receipt->id,
            'supplier_invoice_line_id' => (string) $invoiceLine1->id,
            'qty_diterima' => 2,
        ]);

        $this->assertDatabaseHas('supplier_receipt_lines', [
            'supplier_receipt_id' => (string) $receipt->id,
            'supplier_invoice_line_id' => (string) $invoiceLine2->id,
            'qty_diterima' => 3,
        ]);

        $receiptLine1 = DB::table('supplier_receipt_lines')
            ->where('supplier_receipt_id', (string) $receipt->id)
            ->where('supplier_invoice_line_id', (string) $invoiceLine1->id)
            ->first();

        $receiptLine2 = DB::table('supplier_receipt_lines')
            ->where('supplier_receipt_id', (string) $receipt->id)
            ->where('supplier_invoice_line_id', (string) $invoiceLine2->id)
            ->first();

        $this->assertNotNull($receiptLine1);
        $this->assertNotNull($receiptLine2);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => (string) $receiptLine1->id,
            'tanggal_mutasi' => '2026-03-12',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-2',
            'movement_type' => 'stock_in',
            'source_type' => 'supplier_receipt_line',
            'source_id' => (string) $receiptLine2->id,
            'tanggal_mutasi' => '2026-03-12',
            'qty_delta' => 3,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 2,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-2',
            'qty_on_hand' => 3,
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
        $this->assertDatabaseCount('supplier_payments', 0);
        $this->assertDatabaseCount('supplier_receipts', 0);
        $this->assertDatabaseCount('supplier_receipt_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
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
        $this->assertDatabaseCount('supplier_payments', 0);
        $this->assertDatabaseCount('supplier_receipts', 0);
        $this->assertDatabaseCount('supplier_receipt_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
    }

    public function test_create_supplier_invoice_endpoint_can_disable_auto_receive_and_reuse_existing_supplier(): void
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
            'auto_receive' => false,
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

        $invoice = DB::table('supplier_invoices')
            ->where('supplier_id', 'supplier-1')
            ->first();

        $this->assertNotNull($invoice);

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_invoice_id' => (string) $invoice->id,
            'amount_rupiah' => 20000,
            'paid_at' => '2026-01-30',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);

        $this->assertDatabaseCount('supplier_receipts', 0);
        $this->assertDatabaseCount('supplier_receipt_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('product_inventory', 0);
    }
}
