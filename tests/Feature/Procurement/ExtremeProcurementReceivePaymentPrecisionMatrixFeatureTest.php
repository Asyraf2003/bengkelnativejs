<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsProcurementReceivePaymentPrecisionMatrixFixture;
use Tests\TestCase;

final class ExtremeProcurementReceivePaymentPrecisionMatrixFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsProcurementReceivePaymentPrecisionMatrixFixture;

    public function test_receive_exact_remaining_quantity_after_previous_receipt_is_allowed(): void
    {
        $this->loginAsKasir();
        $this->seedBaseProcurementState();

        $this->recordReceipt('2026-03-16', 6);

        $response = $this->postJson('/procurement/supplier-invoices/invoice-1/receive', [
            'tanggal_terima' => '2026-03-17',
            'lines' => [[
                'supplier_invoice_line_id' => 'invoice-line-1',
                'qty_diterima' => 4,
            ]],
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('supplier_receipts', 2);
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 10,
        ]);
        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 100000,
        ]);
    }

    public function test_receive_more_than_remaining_quantity_by_one_is_rejected(): void
    {
        $this->loginAsKasir();
        $this->seedBaseProcurementState();

        $this->recordReceipt('2026-03-16', 9);

        $response = $this->postJson('/procurement/supplier-invoices/invoice-1/receive', [
            'tanggal_terima' => '2026-03-17',
            'lines' => [[
                'supplier_invoice_line_id' => 'invoice-line-1',
                'qty_diterima' => 2,
            ]],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('supplier_receipts', 1);
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 9,
        ]);
    }

    public function test_partial_payment_can_leave_exact_one_rupiah_outstanding(): void
    {
        $this->seedBaseProcurementState();

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-20',
                'amount' => 99999,
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHas('success', 'Pembayaran supplier berhasil dicatat.');

        $rows = $this->payableRows();

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]['outstanding_rupiah']);
        $this->assertSame('due_today', $rows[0]['due_status']);
    }

    public function test_exact_follow_up_payment_settles_invoice_without_negative_outstanding(): void
    {
        $this->seedBaseProcurementState();

        $this->actingAs($this->admin())
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-19',
                'amount' => 99999,
            ]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-20',
                'amount' => 1,
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHas('success', 'Pembayaran supplier berhasil dicatat.');

        $rows = $this->payableRows();

        $this->assertSame(0, $rows[0]['outstanding_rupiah']);
        $this->assertSame('settled', $rows[0]['due_status']);
    }

    public function test_payment_exceeding_outstanding_by_one_rupiah_is_rejected(): void
    {
        $this->seedBaseProcurementState();

        $this->actingAs($this->admin())
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-19',
                'amount' => 99999,
            ]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-20',
                'amount' => 2,
            ]);

        $response->assertRedirect(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertSessionHasErrors([
                'supplier_payment' => 'Nominal pembayaran melebihi outstanding invoice supplier.',
            ]);

        $rows = $this->payableRows();

        $this->assertSame(1, $rows[0]['outstanding_rupiah']);
    }

    public function test_receive_and_payment_precision_keep_invoice_grain_reporting_consistent(): void
    {
        $this->loginAsKasir();
        $this->seedBaseProcurementState();
        $this->recordReceipt('2026-03-16', 4);

        $this->actingAs($this->admin())
            ->post(route('admin.procurement.supplier-invoices.payments.store', ['supplierInvoiceId' => 'invoice-1']), [
                'payment_date' => '2026-03-20',
                'amount' => 30000,
            ]);

        $rows = $this->payableRows();

        $this->assertCount(1, $rows);
        $this->assertSame('invoice-1', $rows[0]['supplier_invoice_id']);
        $this->assertSame(30000, $rows[0]['total_paid_rupiah']);
        $this->assertSame(70000, $rows[0]['outstanding_rupiah']);
        $this->assertSame(1, $rows[0]['receipt_count']);
        $this->assertSame(4, $rows[0]['total_received_qty']);
    }

    private function admin(): User
    {
        $u = User::query()->create([
            'name' => 'Admin Receive Payment Matrix',
            'email' => 'admin-receive-payment-' . uniqid() . '@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $u->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        return $u;
    }
}
