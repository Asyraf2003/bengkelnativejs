<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspacePartialTransferFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_store_workspace_with_selected_partial_transfer_payment_and_redirect_to_history(): void
    {
        $this->loginAsKasir();
        $user = User::query()->create(['name' => 'Kasir Aktif', 'email' => 'partialtf@example.test', 'password' => 'password']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'kasir']);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'note' => ['customer_name' => 'Budi', 'customer_phone' => '08123', 'transaction_date' => '2026-03-15'],
            'items' => [
                ['entry_mode' => 'service', 'part_source' => 'none', 'pay_now' => 1, 'service' => ['name' => 'Servis A', 'price_rupiah' => 50000, 'notes' => ''], 'product_lines' => [['product_id' => '', 'qty' => '', 'unit_price_rupiah' => '']], 'external_purchase_lines' => [['label' => '', 'qty' => '', 'unit_cost_rupiah' => '']]],
                ['entry_mode' => 'service', 'part_source' => 'none', 'pay_now' => 0, 'service' => ['name' => 'Servis B', 'price_rupiah' => 100000, 'notes' => ''], 'product_lines' => [['product_id' => '', 'qty' => '', 'unit_price_rupiah' => '']], 'external_purchase_lines' => [['label' => '', 'qty' => '', 'unit_cost_rupiah' => '']]],
            ],
            'inline_payment' => ['decision' => 'pay_partial', 'payment_method' => 'transfer', 'paid_at' => '2026-03-15', 'amount_paid_rupiah' => 50000],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));
        $this->assertDatabaseHas('customer_payments', ['amount_rupiah' => 50000, 'paid_at' => '2026-03-15']);
    }
}
