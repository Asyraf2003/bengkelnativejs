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
        foreach ($this->protectedRoutes() as $uri) {
            $response = $this->postJson($uri, []);

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

        foreach ($this->protectedRoutes() as $uri) {
            $response = $this->actingAs($user)->postJson($uri, []);

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
     * @return list<string>
     */
    private function protectedRoutes(): array
    {
        return [
            '/product-catalog/products/create',
            '/product-catalog/products/test-product-id/update',
            '/procurement/supplier-invoices/create',
            '/procurement/supplier-invoices/test-supplier-invoice-id/receive',
        ];
    }
}
