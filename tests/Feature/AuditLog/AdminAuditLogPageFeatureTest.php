<?php

declare(strict_types=1);

namespace Tests\Feature\AuditLog;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminAuditLogPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_audit_logs_with_reason(): void
    {
        $user = $this->createUserWithRole('admin-audit@example.test', 'admin');

        DB::table('audit_logs')->insert([
            'event' => 'supplier_invoice_voided',
            'context' => json_encode([
                'supplier_invoice_id' => 'inv-test-001',
                'reason' => 'Salah input sebelum penerimaan barang.',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => '2026-04-26 10:00:00',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('supplier_invoice_voided');
        $response->assertSee('Salah input sebelum penerimaan barang.');
    }

    public function test_admin_audit_log_search_filters_event_and_context(): void
    {
        $user = $this->createUserWithRole('admin-audit-search@example.test', 'admin');

        DB::table('audit_logs')->insert([
            [
                'event' => 'visible_event_token',
                'context' => json_encode(['reason' => 'VISIBLE_REASON_TOKEN'], JSON_THROW_ON_ERROR),
                'created_at' => '2026-04-26 10:00:00',
            ],
            [
                'event' => 'hidden_event_token',
                'context' => json_encode(['reason' => 'HIDDEN_REASON_TOKEN'], JSON_THROW_ON_ERROR),
                'created_at' => '2026-04-26 10:01:00',
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.audit-logs.index', ['q' => 'VISIBLE_REASON_TOKEN']));

        $response->assertOk();
        $response->assertSee('visible_event_token');
        $response->assertSee('VISIBLE_REASON_TOKEN');
        $response->assertDontSee('hidden_event_token');
        $response->assertDontSee('HIDDEN_REASON_TOKEN');
    }

    public function test_admin_audit_log_page_uses_twenty_items_per_page(): void
    {
        $user = $this->createUserWithRole('admin-audit-pagination@example.test', 'admin');

        DB::table('audit_logs')->insert([
            'event' => 'oldest_event_token',
            'context' => json_encode(['reason' => 'OLDEST_AUDIT_REASON_TOKEN'], JSON_THROW_ON_ERROR),
            'created_at' => '2026-04-26 09:00:00',
        ]);

        for ($index = 1; $index <= 20; $index++) {
            DB::table('audit_logs')->insert([
                'event' => 'newer_event_token_' . $index,
                'context' => json_encode(['reason' => 'NEWER_AUDIT_REASON_TOKEN_' . $index], JSON_THROW_ON_ERROR),
                'created_at' => '2026-04-26 10:00:00',
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertSee('NEWER_AUDIT_REASON_TOKEN_20');
        $response->assertDontSee('OLDEST_AUDIT_REASON_TOKEN');
    }

    public function test_cashier_cannot_read_admin_audit_logs(): void
    {
        $user = $this->createUserWithRole('cashier-audit-denied@example.test', 'kasir');

        $response = $this
            ->actingAs($user)
            ->get(route('admin.audit-logs.index'));

        $response->assertRedirect(route('cashier.dashboard'));
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
