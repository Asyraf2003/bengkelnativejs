<?php

declare(strict_types=1);

namespace Tests\Feature\IdentityAccess;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DisableAdminTransactionCapabilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_disable_capability_endpoint_updates_state_and_writes_audit_log(): void
    {
        DB::table('actor_accesses')->insert([
            'actor_id' => 'admin-1',
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->insert([
            'actor_id' => 'admin-1',
            'active' => true,
        ]);

        $response = $this->postJson('/identity-access/admin-transaction-capability/disable', [
            'target_actor_id' => 'admin-1',
            'performed_by_actor_id' => 'owner-1',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('admin_transaction_capability_states', [
            'actor_id' => 'admin-1',
            'active' => 0,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'admin_transaction_capability_disabled',
            'context' => json_encode([
                'target_actor_id' => 'admin-1',
                'performed_by_actor_id' => 'owner-1',
                'capability' => 'admin_transaction_entry',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
