<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProcurementFixture;
use Tests\TestCase;

final class RecordSupplierPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProcurementFixture;

    public function test_guest_is_redirected_to_login_when_recording_supplier_payment(): void
    {
        $this->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']))
            ->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_recording_supplier_payment(): void
    {
        $response = $this->actingAs($this->user('kasir'))
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_record_partial_supplier_payment(): void
    {
        $this->seedSupplierInvoiceFixture();

        $response = $this
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->actingAs($this->user('admin'))
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-20',
                'amount' => 30000,
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));
        $response->assertSessionHas('success', 'Pembayaran supplier berhasil dicatat.');

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_invoice_id' => 'invoice-1',
            'amount_rupiah' => 30000,
            'paid_at' => '2026-03-20',
            'proof_status' => 'pending',
            'proof_storage_path' => null,
        ]);

        $context = (string) DB::table('audit_logs')
            ->where('event', 'supplier_payment_recorded')
            ->value('context');

        $this->assertStringContainsString('invoice-1', $context);
        $this->assertStringContainsString('30000', $context);
    }

    public function test_admin_cannot_pay_more_than_outstanding(): void
    {
        $this->seedSupplierInvoiceFixture();
        $this->seedMinimalSupplierPayment('payment-1', 'invoice-1', 90000, '2026-03-18', 'pending');

        $response = $this
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->actingAs($this->user('admin'))
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-20',
                'amount' => 20000,
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']));
        $response->assertSessionHasErrors([
            'supplier_payment' => 'Nominal pembayaran melebihi outstanding invoice supplier.',
        ]);
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $role . '-supplier-payment@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function seedSupplierInvoiceFixture(): void
    {
        $this->seedMinimalSupplier('supplier-1', 'PT Supplier Test', 'pt supplier test');
        $this->seedMinimalProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 75000);

        $this->seedMinimalSupplierInvoice(
            'invoice-1',
            'supplier-1',
            '2026-03-15',
            '2026-04-15',
            100000,
            'PT Supplier Test'
        );

        $this->seedMinimalSupplierInvoiceLine(
            'invoice-line-1',
            'invoice-1',
            'product-1',
            2,
            100000,
            50000,
            'KB-001',
            'Ban Luar',
            'Federal',
            100
        );
    }
}
