<?php

declare(strict_types=1);

namespace Tests\Feature\AuditLog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminAuditEventPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_audit_events_with_reason(): void
    {
        $user = $this->createUserWithRole('admin-audit-event@example.test', 'admin');

        $this->insertAuditEvent(
            'audit-event-test-001',
            'product_updated',
            'product_catalog',
            'product',
            'product-test-001',
            'Perbaikan harga jual.',
            (string) $user->getAuthIdentifier(),
            '2026-04-26 11:00:00',
        );

        $response = $this
            ->actingAs($user)
            ->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('product_updated');
        $response->assertSee('Perbaikan harga jual.');
        $response->assertSee('product_catalog');
        $response->assertSee('product-test-001');
    }

    public function test_admin_audit_log_search_filters_audit_event_reason(): void
    {
        $user = $this->createUserWithRole('admin-audit-event-search@example.test', 'admin');

        $this->insertAuditEvent(
            'audit-event-visible-001',
            'employee_updated',
            'employee_finance',
            'employee',
            'employee-visible-001',
            'VISIBLE_AUDIT_EVENT_REASON_TOKEN',
            (string) $user->getAuthIdentifier(),
            '2026-04-26 11:00:00',
        );

        $this->insertAuditEvent(
            'audit-event-hidden-001',
            'employee_hidden_updated',
            'employee_finance',
            'employee',
            'employee-hidden-001',
            'HIDDEN_AUDIT_EVENT_REASON_TOKEN',
            (string) $user->getAuthIdentifier(),
            '2026-04-26 11:01:00',
        );

        $response = $this
            ->actingAs($user)
            ->get(route('admin.audit-logs.index', ['q' => 'VISIBLE_AUDIT_EVENT_REASON_TOKEN']));

        $response->assertOk();
        $response->assertSee('employee_updated');
        $response->assertSee('VISIBLE_AUDIT_EVENT_REASON_TOKEN');
        $response->assertDontSee('employee_hidden_updated');
        $response->assertDontSee('HIDDEN_AUDIT_EVENT_REASON_TOKEN');
    }

    private function insertAuditEvent(
        string $id,
        string $eventName,
        string $boundedContext,
        string $aggregateType,
        string $aggregateId,
        string $reason,
        string $actorId,
        string $occurredAt,
    ): void {
        DB::table('audit_events')->insert([
            'id' => $id,
            'bounded_context' => $boundedContext,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_name' => $eventName,
            'actor_id' => $actorId,
            'actor_role' => 'admin',
            'reason' => $reason,
            'source_channel' => 'web_admin',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => $occurredAt,
            'metadata_json' => json_encode([
                'aggregate_id' => $aggregateId,
                'proof' => 'audit event page test',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
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
