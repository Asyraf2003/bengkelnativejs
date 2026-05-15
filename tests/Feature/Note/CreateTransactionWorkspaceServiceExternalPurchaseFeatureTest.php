<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceServiceExternalPurchaseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_store_workspace_service_with_external_purchase_payload_and_redirect_to_history(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Service External',
            'email' => 'service-external@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
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
                    'name' => 'Servis Bearing',
                    'price_rupiah' => 80000,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => '',
                    'qty' => '',
                    'unit_price_rupiah' => '',
                ]],
                'external_purchase_lines' => [[
                    'label' => 'Bearing NTN',
                    'qty' => 1,
                    'unit_cost_rupiah' => 120000,
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
            'total_rupiah' => 200000,
        ]);
        $this->assertDatabaseCount('work_item_service_details', 1);
        $this->assertDatabaseCount('work_item_external_purchase_lines', 1);
    }
}
