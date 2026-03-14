<?php

declare(strict_types=1);

namespace Tests\Feature\IdentityAccess;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionEntryMiddlewareFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected_for_all_protected_transaction_routes(): void
    {
        foreach ($this->protectedRoutes() as $route) {
            $response = $this->postJson($route['uri'], $route['payload']);

            $response->assertStatus(401);
            $response->assertJson([
                'success' => false,
                'data' => null,
                'message' => 'Autentikasi dibutuhkan.',
                'errors' => [
                    'auth' => ['UNAUTHENTICATED'],
                ],
            ]);
        }
    }

    public function test_authenticated_admin_without_active_capability_is_rejected_for_all_protected_transaction_routes(): void
    {
        $user = User::query()->create([
            'name' => 'Admin Tanpa Capability',
            'email' => 'admin-no-capability@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        foreach ($this->protectedRoutes() as $route) {
            $response = $this->actingAs($user)->postJson($route['uri'], $route['payload']);

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'data' => null,
                'message' => 'Admin belum diizinkan input transaksi.',
                'errors' => [
                    'capability' => ['ADMIN_TRANSACTION_CAPABILITY_DISABLED'],
                ],
            ]);
        }
    }

    /**
     * @return list<array{uri:string,payload:array<string,mixed>}>
     */
    private function protectedRoutes(): array
    {
        return [
            [
                'uri' => '/notes/create',
                'payload' => [
                    'customer_name' => 'Budi Santoso',
                    'transaction_date' => '2026-03-14',
                ],
            ],
            [
                'uri' => '/product-catalog/products/create',
                'payload' => [
                    'nama_barang' => 'Produk Test',
                    'merek' => 'Merek Test',
                    'harga_jual' => 15000,
                ],
            ],
            [
                'uri' => '/product-catalog/products/test-product-id/update',
                'payload' => [
                    'nama_barang' => 'Produk Test Update',
                    'merek' => 'Merek Test',
                    'harga_jual' => 16000,
                ],
            ],
            [
                'uri' => '/procurement/supplier-invoices/create',
                'payload' => [
                    'supplier_id' => 'supplier-test-id',
                    'invoice_number' => 'INV-TEST-001',
                    'invoice_date' => '2026-03-14',
                    'due_date' => '2026-03-21',
                    'lines' => [
                        [
                            'product_id' => 'product-test-id',
                            'qty' => 1,
                            'unit_price_rupiah' => 10000,
                        ],
                    ],
                ],
            ],
            [
                'uri' => '/procurement/supplier-invoices/test-supplier-invoice-id/receive',
                'payload' => [
                    'receipt_date' => '2026-03-14',
                ],
            ],
        ];
    }
}
