<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class UpdateProductFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_product_endpoint_updates_existing_product(): void
    {
        $this->loginAsKasir();
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        $response = $this->postJson('/product-catalog/products/product-1/update', [
            'kode_barang' => 'KB-001-REV',
            'nama_barang' => 'Supra X',
            'merek' => 'Federal',
            'ukuran' => 110,
            'harga_jual' => 18000,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => 'product-1',
            'kode_barang' => 'KB-001-REV',
            'nama_barang' => 'Supra X',
            'merek' => 'Federal',
            'ukuran' => 110,
            'harga_jual' => 18000,
        ]);
    }

    public function test_update_product_endpoint_rejects_duplicate_variant(): void
    {
        $this->loginAsKasir();
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => null,
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        DB::table('products')->insert([
            'id' => 'product-2',
            'kode_barang' => null,
            'nama_barang' => 'Vario',
            'merek' => 'Federal',
            'ukuran' => 120,
            'harga_jual' => 16000,
        ]);

        $response = $this->postJson('/product-catalog/products/product-2/update', [
            'kode_barang' => null,
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 17000,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('products', [
            'id' => 'product-2',
            'nama_barang' => 'Vario',
            'merek' => 'Federal',
            'ukuran' => 120,
            'harga_jual' => 16000,
        ]);
    }

    public function test_update_product_endpoint_returns_failure_when_product_not_found(): void
    {
        $this->loginAsKasir();
        $response = $this->postJson('/product-catalog/products/missing-product/update', [
            'kode_barang' => 'KB-404',
            'nama_barang' => 'Revo',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 14000,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('products', 0);
    }
}
