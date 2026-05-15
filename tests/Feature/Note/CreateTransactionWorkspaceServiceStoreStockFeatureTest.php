<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceServiceStoreStockFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_store_workspace_service_with_store_stock_payload_and_redirect_to_history(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Service Store Stock',
            'email' => 'service-store-stock@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Oli Mesin',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 15000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 100000,
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'note' => [
                'customer_name' => 'Budi',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Tune Up',
                    'price_rupiah' => 70000,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => 'product-1',
                    'qty' => 2,
                    'unit_price_rupiah' => 20000,
                ]],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));
        $this->assertDatabaseHas('notes', [
            'customer_name' => 'Budi',
            'total_rupiah' => 110000,
        ]);
        $this->assertDatabaseCount('work_item_service_details', 1);
        $this->assertDatabaseCount('work_item_store_stock_lines', 1);
    }
}
