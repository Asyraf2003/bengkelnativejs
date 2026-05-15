<?php

declare(strict_types=1);

namespace Tests\Feature\MobileApi\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
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

    public function test_admin_can_read_supplier_invoice_list_with_real_projection_row(): void
    {
        $token = $this->loginMobileToken(
            email: 'mobile-admin-supplier-invoice-list-row@example.test',
            role: 'admin',
        );

        $this->seedSupplierInvoiceForMobileApi();

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/supplier-invoices?q=FTR-MOBILE-001&payment_status=outstanding&page=1&per_page=10');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'rows' => [
                    [
                        'supplier_invoice_id' => 'supplier-invoice-mobile-001',
                        'nomor_faktur' => 'FTR-MOBILE-001',
                        'supplier_nama_pt_pengirim_current' => 'PT Mobile Supplier',
                        'supplier_nama_pt_pengirim_snapshot' => 'PT Mobile Supplier',
                        'shipment_date' => '2026-05-10',
                        'due_date' => '2026-05-20',
                        'grand_total_rupiah' => 30000,
                        'total_paid_rupiah' => 0,
                        'outstanding_rupiah' => 30000,
                        'payment_count' => 0,
                        'receipt_count' => 0,
                        'total_received_qty' => 0,
                        'proof_attachment_count' => 0,
                        'can_record_payment' => true,
                        'has_uploaded_proof' => false,
                        'policy_state' => 'editable',
                    ],
                ],
            ],
            'meta' => [
                'page' => 1,
                'per_page' => 10,
                'filters' => [
                    'q' => 'FTR-MOBILE-001',
                    'payment_status' => 'outstanding',
                ],
            ],
            'errors' => null,
        ]);
    }

    public function test_admin_can_read_supplier_invoice_detail_with_current_lines(): void
    {
        $token = $this->loginMobileToken(
            email: 'mobile-admin-supplier-invoice-detail-row@example.test',
            role: 'admin',
        );

        $this->seedSupplierInvoiceForMobileApi();

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/supplier-invoices/supplier-invoice-mobile-001');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'summary' => [
                    'supplier_invoice_id' => 'supplier-invoice-mobile-001',
                    'nomor_faktur' => 'FTR-MOBILE-001',
                    'supplier_id' => 'supplier-mobile-001',
                    'supplier_nama_pt_pengirim_current' => 'PT Mobile Supplier',
                    'supplier_nama_pt_pengirim_snapshot' => 'PT Mobile Supplier',
                    'shipment_date' => '2026-05-10',
                    'due_date' => '2026-05-20',
                    'grand_total_rupiah' => 30000,
                    'total_paid_rupiah' => 0,
                    'outstanding_rupiah' => 30000,
                    'receipt_count' => 0,
                    'total_received_qty' => 0,
                    'voided_at' => null,
                    'void_reason' => null,
                    'policy_state' => 'editable',
                ],
                'lines' => [
                    [
                        'id' => 'supplier-invoice-line-mobile-001',
                        'supplier_invoice_id' => 'supplier-invoice-mobile-001',
                        'product_id' => 'product-mobile-supplier-001',
                        'kode_barang' => 'SP-MOB-001',
                        'nama_barang' => 'Sparepart Mobile',
                        'merek' => 'Federal',
                        'ukuran' => 80,
                        'qty_pcs' => 2,
                        'line_total_rupiah' => 30000,
                        'unit_cost_rupiah' => 15000,
                    ],
                ],
            ],
            'meta' => null,
            'errors' => null,
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

    private function seedSupplierInvoiceForMobileApi(): void
    {
        DB::table('suppliers')->insert([
            'id' => 'supplier-mobile-001',
            'nama_pt_pengirim' => 'PT Mobile Supplier',
            'nama_pt_pengirim_normalized' => 'pt mobile supplier',
        ]);

        DB::table('supplier_invoices')->insert([
            'id' => 'supplier-invoice-mobile-001',
            'supplier_id' => 'supplier-mobile-001',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Mobile Supplier',
            'nomor_faktur' => 'FTR-MOBILE-001',
            'nomor_faktur_normalized' => 'ftr-mobile-001',
            'document_kind' => 'invoice',
            'lifecycle_status' => 'active',
            'tanggal_pengiriman' => '2026-05-10',
            'jatuh_tempo' => '2026-05-20',
            'grand_total_rupiah' => 30000,
            'last_revision_no' => 1,
        ]);

        DB::table('products')->insert([
            'id' => 'product-mobile-supplier-001',
            'kode_barang' => 'SP-MOB-001',
            'nama_barang' => 'Sparepart Mobile',
            'nama_barang_normalized' => 'sparepart mobile',
            'merek' => 'Federal',
            'merek_normalized' => 'federal',
            'ukuran' => 80,
            'harga_jual' => 15000,
        ]);

        DB::table('supplier_invoice_lines')->insert([
            'id' => 'supplier-invoice-line-mobile-001',
            'supplier_invoice_id' => 'supplier-invoice-mobile-001',
            'revision_no' => 1,
            'is_current' => true,
            'line_no' => 1,
            'product_id' => 'product-mobile-supplier-001',
            'product_kode_barang_snapshot' => 'SP-MOB-001',
            'product_nama_barang_snapshot' => 'Sparepart Mobile',
            'product_merek_snapshot' => 'Federal',
            'product_ukuran_snapshot' => 80,
            'qty_pcs' => 2,
            'line_total_rupiah' => 30000,
            'unit_cost_rupiah' => 15000,
        ]);

        DB::table('supplier_invoice_list_projection')->insert([
            'supplier_invoice_id' => 'supplier-invoice-mobile-001',
            'supplier_id' => 'supplier-mobile-001',
            'nomor_faktur' => 'FTR-MOBILE-001',
            'nomor_faktur_normalized' => 'ftr-mobile-001',
            'supplier_nama_pt_pengirim_snapshot' => 'PT Mobile Supplier',
            'shipment_date' => '2026-05-10',
            'due_date' => '2026-05-20',
            'grand_total_rupiah' => 30000,
            'total_paid_rupiah' => 0,
            'outstanding_rupiah' => 30000,
            'payment_count' => 0,
            'receipt_count' => 0,
            'total_received_qty' => 0,
            'proof_attachment_count' => 0,
            'lifecycle_status' => 'active',
            'payment_status' => 'outstanding',
            'last_revision_no' => 1,
            'projected_at' => now(),
        ]);
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
