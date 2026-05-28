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

        $noteId = (string) DB::table('notes')->value('id');
        $workItemId = (string) DB::table('work_items')->value('id');
        $storeStockLineId = (string) DB::table('work_item_store_stock_lines')->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'customer_name' => 'Budi',
            'total_rupiah' => 110000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 110000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Tune Up',
            'service_price_rupiah' => 70000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => $storeStockLineId,
            'work_item_id' => $workItemId,
            'product_id' => 'product-1',
            'qty' => 2,
            'line_total_rupiah' => 40000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $storeStockLineId,
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -20000,
        ]);
    }
    public function test_cashier_can_store_workspace_service_store_stock_with_package_total_auto_split(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Store Stock',
            'email' => 'service-store-stock-package@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-package-1',
            'kode_barang' => 'KB-PKG-001',
            'nama_barang' => 'Kampas Rem',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 40000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-package-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-package-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 250000,
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
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 150000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Rem',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => 'product-package-1',
                    'qty' => 1,
                    'unit_price_rupiah' => 40000,
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

        $noteId = (string) DB::table('notes')->where('customer_name', 'Budi')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 150000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 150000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Rem',
            'service_price_rupiah' => 110000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-1',
            'qty' => 1,
            'line_total_rupiah' => 40000,
        ]);
    }

    public function test_cashier_can_store_workspace_service_store_stock_package_auto_split_with_two_different_products(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Multi Store Stock',
            'email' => 'service-store-stock-package-multi@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            [
                'id' => 'product-package-multi-a',
                'kode_barang' => 'KB-PKG-MULTI-001',
                'nama_barang' => 'Oli Paket Multi',
                'merek' => 'Federal',
                'ukuran' => null,
                'harga_jual' => 50000,
            ],
            [
                'id' => 'product-package-multi-b',
                'kode_barang' => 'KB-PKG-MULTI-002',
                'nama_barang' => 'Busi Paket Multi',
                'merek' => 'NGK',
                'ukuran' => null,
                'harga_jual' => 30000,
            ],
        ]);

        DB::table('product_inventory')->insert([
            [
                'product_id' => 'product-package-multi-a',
                'qty_on_hand' => 10,
            ],
            [
                'product_id' => 'product-package-multi-b',
                'qty_on_hand' => 10,
            ],
        ]);

        DB::table('product_inventory_costing')->insert([
            [
                'product_id' => 'product-package-multi-a',
                'avg_cost_rupiah' => 35000,
                'inventory_value_rupiah' => 350000,
            ],
            [
                'product_id' => 'product-package-multi-b',
                'avg_cost_rupiah' => 20000,
                'inventory_value_rupiah' => 200000,
            ],
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'note' => [
                'customer_name' => 'Budi Multi Package',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Paket Multi Part',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [
                    [
                        'product_id' => 'product-package-multi-a',
                        'qty' => 2,
                        'unit_price_rupiah' => 50000,
                    ],
                    [
                        'product_id' => 'product-package-multi-b',
                        'qty' => 1,
                        'unit_price_rupiah' => 30000,
                    ],
                ],
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

        $noteId = (string) DB::table('notes')->where('customer_name', 'Budi Multi Package')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 250000,
        ]);

        $this->assertDatabaseCount('work_items', 1);
        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Paket Multi Part',
            'service_price_rupiah' => 120000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseCount('work_item_store_stock_lines', 2);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-multi-a',
            'qty' => 2,
            'line_total_rupiah' => 100000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-multi-b',
            'qty' => 1,
            'line_total_rupiah' => 30000,
        ]);

        $storeStockLineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-package-multi-a',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $storeStockLineIds['product-package-multi-a'],
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 35000,
            'total_cost_rupiah' => -70000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-package-multi-b',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $storeStockLineIds['product-package-multi-b'],
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -1,
            'unit_cost_rupiah' => 20000,
            'total_cost_rupiah' => -20000,
        ]);

        $audit = DB::table('audit_logs')
            ->where('event', 'transaction_workspace_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);

        $context = json_decode((string) $audit->context, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($noteId, $context['note_id'] ?? null);
        $this->assertSame([
            [
                'work_item_id' => $workItemId,
                'store_stock_line_id' => (string) $storeStockLineIds['product-package-multi-a'],
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'sparepart_total_rupiah' => 100000,
                'service_price_rupiah' => 120000,
                'product_id' => 'product-package-multi-a',
                'qty' => 2,
                'product_unit_price_rupiah' => 50000,
            ],
            [
                'work_item_id' => $workItemId,
                'store_stock_line_id' => (string) $storeStockLineIds['product-package-multi-b'],
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'sparepart_total_rupiah' => 30000,
                'service_price_rupiah' => 120000,
                'product_id' => 'product-package-multi-b',
                'qty' => 1,
                'product_unit_price_rupiah' => 30000,
            ],
        ], $context['package_allocations'] ?? null);
    }

    public function test_cashier_can_store_workspace_service_store_stock_package_total_equal_sparepart_minimum(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Minimum',
            'email' => 'service-store-stock-package-min@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-package-min-1',
            'kode_barang' => 'KB-PKG-MIN-001',
            'nama_barang' => 'Busi',
            'merek' => 'NGK',
            'ukuran' => null,
            'harga_jual' => 40000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-package-min-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-package-min-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 250000,
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
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 40000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Busi',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => 'product-package-min-1',
                    'qty' => 1,
                    'unit_price_rupiah' => 40000,
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

        $noteId = (string) DB::table('notes')->where('customer_name', 'Budi')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 40000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 40000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Busi',
            'service_price_rupiah' => 0,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-min-1',
            'qty' => 1,
            'line_total_rupiah' => 40000,
        ]);
    }

    public function test_cashier_cannot_store_workspace_service_store_stock_package_total_below_sparepart_minimum(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Below Minimum',
            'email' => 'service-store-stock-package-below-min@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-package-below-min-1',
            'kode_barang' => 'KB-PKG-BELOW-MIN-001',
            'nama_barang' => 'Busi Racing',
            'merek' => 'NGK',
            'ukuran' => null,
            'harga_jual' => 40000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-package-below-min-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-package-below-min-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 250000,
        ]);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), [
                'note' => [
                    'customer_name' => 'Budi',
                    'customer_phone' => '08123',
                    'transaction_date' => '2026-03-15',
                ],
                'items' => [[
                    'entry_mode' => 'service',
                    'part_source' => 'none',
                    'pricing_mode' => 'package_auto_split',
                    'package_total_rupiah' => 30000,
                    'pay_now' => 0,
                    'service' => [
                        'name' => 'Servis Busi Racing',
                        'price_rupiah' => 0,
                        'notes' => '',
                    ],
                    'product_lines' => [[
                        'product_id' => 'product-package-below-min-1',
                        'qty' => 1,
                        'unit_price_rupiah' => 40000,
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

        $response->assertRedirect(route('cashier.notes.workspace.create'));
        $response->assertSessionHasErrors([
            'workspace' => 'Harga paket tidak boleh lebih kecil dari total harga sparepart.',
        ]);

        $this->assertDatabaseCount('notes', 0);
        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertDatabaseCount('payment_component_allocations', 0);
    }

}
