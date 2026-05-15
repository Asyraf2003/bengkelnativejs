<?php

declare(strict_types=1);

namespace Tests\Feature\AuditLog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminAuditLogUnifiedSourcePageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_legacy_and_v2_audit_sources_together(): void
    {
        $admin = $this->createUserWithRole('admin-audit-unified@example.test', 'admin');
        $actorId = (string) $admin->getAuthIdentifier();

        DB::table('audit_logs')->insert([
            'event' => 'legacy_stock_adjusted',
            'context' => json_encode([
                'product_id' => 'legacy-product-001',
                'actor_id' => $actorId,
                'actor_role' => 'admin',
                'reason' => 'LEGACY_REASON_VISIBLE_TOKEN',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => '2026-04-26 10:00:00',
        ]);

        DB::table('audit_events')->insert([
            'id' => 'audit-event-unified-001',
            'bounded_context' => 'product_catalog',
            'aggregate_type' => 'product',
            'aggregate_id' => 'v2-product-001',
            'event_name' => 'product_updated',
            'actor_id' => $actorId,
            'actor_role' => 'admin',
            'reason' => 'V2_REASON_VISIBLE_TOKEN',
            'source_channel' => 'admin_web',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-04-26 11:00:00',
            'metadata_json' => json_encode(['revision_no' => 2], JSON_THROW_ON_ERROR),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('audit_logs');
        $response->assertSee('legacy_stock_adjusted');
        $response->assertSee('legacy-product-001');
        $response->assertSee('LEGACY_REASON_VISIBLE_TOKEN');

        $response->assertSee('audit_events');
        $response->assertSee('product_updated');
        $response->assertSee('product_catalog');
        $response->assertSee('v2-product-001');
        $response->assertSee('V2_REASON_VISIBLE_TOKEN');

        $response->assertSeeInOrder([
            'V2_REASON_VISIBLE_TOKEN',
            'LEGACY_REASON_VISIBLE_TOKEN',
        ]);
    }

    public function test_admin_can_search_v2_audit_event_by_entity_id(): void
    {
        $admin = $this->createUserWithRole('admin-audit-entity-search@example.test', 'admin');

        DB::table('audit_events')->insert([
            [
                'id' => 'audit-event-entity-visible',
                'bounded_context' => 'employee_finance',
                'aggregate_type' => 'employee',
                'aggregate_id' => 'ENTITY_SEARCH_VISIBLE_TOKEN',
                'event_name' => 'employee_updated',
                'actor_id' => (string) $admin->getAuthIdentifier(),
                'actor_role' => 'admin',
                'reason' => 'Visible employee edit reason',
                'source_channel' => 'admin_web',
                'request_id' => null,
                'correlation_id' => null,
                'occurred_at' => '2026-04-26 12:00:00',
                'metadata_json' => null,
            ],
            [
                'id' => 'audit-event-entity-hidden',
                'bounded_context' => 'employee_finance',
                'aggregate_type' => 'employee',
                'aggregate_id' => 'ENTITY_SEARCH_HIDDEN_TOKEN',
                'event_name' => 'employee_updated_hidden',
                'actor_id' => (string) $admin->getAuthIdentifier(),
                'actor_role' => 'admin',
                'reason' => 'Hidden employee edit reason',
                'source_channel' => 'admin_web',
                'request_id' => null,
                'correlation_id' => null,
                'occurred_at' => '2026-04-26 12:01:00',
                'metadata_json' => null,
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['q' => 'ENTITY_SEARCH_VISIBLE_TOKEN']));

        $response->assertOk();
        $response->assertSee('ENTITY_SEARCH_VISIBLE_TOKEN');
        $response->assertSee('Visible employee edit reason');
        $response->assertDontSee('ENTITY_SEARCH_HIDDEN_TOKEN');
        $response->assertDontSee('Hidden employee edit reason');
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
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
