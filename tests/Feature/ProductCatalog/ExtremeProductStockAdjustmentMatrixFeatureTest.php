<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ExtremeProductStockAdjustmentMatrixFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_valid_stock_adjustment_and_reduce_projection_precisely(): void
    {
        $this->seedProductWithInventory();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']), [
                'adjusted_at' => '2026-03-20',
                'qty_issue' => 2,
                'reason' => 'Barang rusak saat audit gudang',
            ]);

        $response->assertRedirect(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->assertSessionHas('success', 'Stock adjustment berhasil dicatat.');

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'stock_adjustment',
            'tanggal_mutasi' => '2026-03-20',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -20000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 3,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 30000,
        ]);
    }

    public function test_admin_can_record_valid_stock_adjustment_with_exact_remaining_stock(): void
    {
        $this->seedProductWithInventory();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']), [
                'adjusted_at' => '2026-03-20',
                'qty_issue' => 5,
                'reason' => 'Semua unit rusak total',
            ]);

        $response->assertRedirect(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->assertSessionHas('success', 'Stock adjustment berhasil dicatat.');

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 0,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 0,
            'inventory_value_rupiah' => 0,
        ]);
    }

    public function test_admin_cannot_record_stock_adjustment_that_would_make_stock_negative(): void
    {
        $this->seedProductWithInventory();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']), [
                'adjusted_at' => '2026-03-20',
                'qty_issue' => 6,
                'reason' => 'User ngaco minta kurangi lebih banyak dari stok',
            ]);

        $response->assertRedirect(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors(['stock_adjustment']);

        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => 'product-1',
            'source_type' => 'stock_adjustment',
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 5,
        ]);
    }

    public function test_admin_cannot_record_stock_adjustment_with_whitespace_only_reason(): void
    {
        $this->seedProductWithInventory();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']), [
                'adjusted_at' => '2026-03-20',
                'qty_issue' => 1,
                'reason' => '   ',
            ]);

        $response->assertRedirect(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors(['reason']);

        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => 'product-1',
            'source_type' => 'stock_adjustment',
        ]);
    }

    public function test_admin_cannot_record_stock_adjustment_with_invalid_date_format(): void
    {
        $this->seedProductWithInventory();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']), [
                'adjusted_at' => '20-03-2026',
                'qty_issue' => 1,
                'reason' => 'Tanggal ngawur',
            ]);

        $response->assertRedirect(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors(['adjusted_at']);

        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => 'product-1',
            'source_type' => 'stock_adjustment',
        ]);
    }

    public function test_admin_cannot_record_stock_adjustment_with_zero_qty_issue(): void
    {
        $this->seedProductWithInventory();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->post(route('admin.products.stock-adjustments.store', ['productId' => 'product-1']), [
                'adjusted_at' => '2026-03-20',
                'qty_issue' => 0,
                'reason' => 'Qty nol tidak valid',
            ]);

        $response->assertRedirect(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors(['qty_issue']);

        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => 'product-1',
            'source_type' => 'stock_adjustment',
        ]);
    }

    private function seedProductWithInventory(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'nama_barang_normalized' => 'ban luar',
            'merek' => 'Federal',
            'merek_normalized' => 'federal',
            'ukuran' => 90,
            'harga_jual' => 35000,
            'reorder_point_qty' => 2,
            'critical_threshold_qty' => 1,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 5,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 50000,
        ]);
    }

    private function admin(): User
    {
        $u = User::query()->create([
            'name' => 'Admin Stock Matrix',
            'email' => 'admin-stock-matrix@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $u->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $u;
    }
}
