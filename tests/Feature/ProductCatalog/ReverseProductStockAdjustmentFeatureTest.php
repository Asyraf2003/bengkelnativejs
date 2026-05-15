<?php

declare(strict_types=1);

namespace Tests\Feature\ProductCatalog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReverseProductStockAdjustmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reverse_stock_adjustment_and_restore_projection_precisely(): void
    {
        $this->seedAdjustedProduct();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->patch(route('admin.products.stock-adjustments.reverse', [
                'productId' => 'product-1',
                'adjustmentId' => 'adjustment-1',
            ]), [
                'reversed_at' => '2026-03-16',
            ]);

        $response->assertRedirect(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->assertSessionHas('success', 'Stock adjustment berhasil direverse.');

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_in',
            'source_type' => 'stock_adjustment_reversal',
            'source_id' => 'adjustment-1',
            'tanggal_mutasi' => '2026-03-16',
            'qty_delta' => 3,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 70000,
        ]);
    }

    public function test_admin_cannot_reverse_same_stock_adjustment_twice(): void
    {
        $this->seedAdjustedProductWithReversal();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->patch(route('admin.products.stock-adjustments.reverse', [
                'productId' => 'product-1',
                'adjustmentId' => 'adjustment-1',
            ]), [
                'reversed_at' => '2026-03-16',
            ]);

        $response->assertRedirect(route('admin.products.stock.edit', ['productId' => 'product-1']))
            ->assertSessionHasErrors(['stock_adjustment_reversal']);
    }

    private function seedAdjustedProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Ban Luar',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 12000,
            'deleted_at' => null,
            'deleted_by_actor_id' => null,
            'delete_reason' => null,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 4,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 40000,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'movement-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'stock_adjustment',
            'source_id' => 'adjustment-1',
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -3,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -30000,
        ]);
    }

    private function seedAdjustedProductWithReversal(): void
    {
        $this->seedAdjustedProduct();

        DB::table('inventory_movements')->insert([
            'id' => 'movement-2',
            'product_id' => 'product-1',
            'movement_type' => 'stock_in',
            'source_type' => 'stock_adjustment_reversal',
            'source_id' => 'adjustment-1',
            'tanggal_mutasi' => '2026-03-16',
            'qty_delta' => 3,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => 30000,
        ]);
    }

    private function admin(): User
    {
        $user = User::query()->create([
            'name' => 'Admin Reverse Stock Adjustment',
            'email' => 'admin-reverse-stock-adjustment-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $user;
    }
}
