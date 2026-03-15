<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateProductFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_endpoint_stores_new_product(): void
    {
        $this->loginAsKasir();
        $response = $this->postJson('/product-catalog/products/create', [
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 15000,
        ]);
    }

    public function test_create_product_endpoint_rejects_duplicate_when_kode_barang_is_not_distinct_exception(): void
    {
        $this->loginAsKasir();
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => null,
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 15000,
        ]);

        $response = $this->postJson('/product-catalog/products/create', [
            'kode_barang' => null,
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 16000,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_create_product_endpoint_allows_same_nama_and_merek_when_kode_barang_exception_applies(): void
    {
        $this->loginAsKasir();
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 15000,
        ]);

        $response = $this->postJson('/product-catalog/products/create', [
            'kode_barang' => 'KB-002',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 17000,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'kode_barang' => 'KB-002',
            'nama_barang' => 'Supra',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 17000,
        ]);
    }

    public function test_create_product_endpoint_validates_harga_jual_must_be_greater_than_zero(): void
    {
        $this->loginAsKasir();
        $response = $this->postJson('/product-catalog/products/create', [
            'kode_barang' => 'KB-003',
            'nama_barang' => 'Vario',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 0,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('products', [
            'kode_barang' => 'KB-003',
            'nama_barang' => 'Vario',
            'merek' => 'Federal',
            'ukuran' => 90,
            'harga_jual' => 0,
        ]);
    }
}
