<?php

declare(strict_types=1);

namespace Tests\Feature\MobileApi\Procurement;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MobileApiSupplierInvoiceReadFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_invoice_list_requires_mobile_api_token(): void
    {
        $response = $this->getJson('/api/v1/supplier-invoices');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Autentikasi diperlukan.',
            'errors' => [
                'token' => ['UNAUTHENTICATED'],
            ],
        ]);
    }

    public function test_cashier_mobile_token_cannot_read_supplier_invoice_list(): void
    {
        $token = $this->loginMobileToken(
            email: 'mobile-kasir-supplier-invoice-list@example.test',
            role: 'kasir',
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/supplier-invoices');

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Akses nota supplier mobile hanya untuk admin.',
            'errors' => [
                'role' => ['ADMIN_ONLY'],
            ],
        ]);
    }

    public function test_admin_can_read_empty_supplier_invoice_list_with_backend_payment_status_terms(): void
    {
        $token = $this->loginMobileToken(
            email: 'mobile-admin-supplier-invoice-list@example.test',
            role: 'admin',
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/supplier-invoices?payment_status=outstanding&page=1&per_page=10');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'rows' => [],
            ],
            'meta' => [
                'page' => 1,
                'per_page' => 10,
                'filters' => [
                    'payment_status' => 'outstanding',
                ],
            ],
            'errors' => null,
        ]);
    }

    public function test_admin_supplier_invoice_detail_returns_safe_not_found_payload(): void
    {
        $token = $this->loginMobileToken(
            email: 'mobile-admin-supplier-invoice-detail@example.test',
            role: 'admin',
        );

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/supplier-invoices/missing-mobile-supplier-invoice');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Nota supplier tidak ditemukan.',
            'errors' => [
                'supplier_invoice' => ['SUPPLIER_INVOICE_NOT_FOUND'],
            ],
        ]);
    }

    private function loginMobileToken(string $email, string $role): string
    {
        $this->createUserWithRole($email, $role);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'password123',
            'device_name' => 'Redmi 12',
        ]);

        $response->assertOk();

        return (string) $response->json('data.token');
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Mobile Supplier Invoice User',
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
