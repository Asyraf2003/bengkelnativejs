<?php

declare(strict_types=1);

namespace Tests\Unit\Application\IdentityAccess\Policies;

use App\Application\IdentityAccess\Policies\TransactionEntryPolicy;
use App\Core\IdentityAccess\Actor\ActorAccess;
use App\Core\IdentityAccess\Capability\AdminTransactionCapabilityState;
use App\Core\IdentityAccess\Role\Role;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use App\Ports\Out\IdentityAccess\AdminTransactionCapabilityStatePort;
use PHPUnit\Framework\TestCase;

final class TransactionEntryPolicyTest extends TestCase
{
    public function test_kasir_is_allowed_to_enter_transaction(): void
    {
        $actors = new InMemoryActorAccessReaderPort([
            'kasir-1' => new ActorAccess('kasir-1', Role::kasir()),
        ]);
        $capabilities = new InMemoryAdminTransactionCapabilityStatePort();
        $audit = new SpyAuditLogPort();

        $policy = new TransactionEntryPolicy($actors, $capabilities, $audit);

        $result = $policy->decide('kasir-1');

        self::assertTrue($result->isSuccess());
        self::assertSame(true, $result->data()['allowed']);
        self::assertSame('kasir', $result->data()['role']);
        self::assertSame('kasir_default_allow', $result->data()['score']);
        self::assertCount(0, $audit->records);
    }

    public function test_admin_without_active_capability_is_denied(): void
    {
        $actors = new InMemoryActorAccessReaderPort([
            'admin-1' => new ActorAccess('admin-1', Role::admin()),
        ]);
        $capabilities = new InMemoryAdminTransactionCapabilityStatePort([
            'admin-1' => AdminTransactionCapabilityState::inactive('admin-1'),
        ]);
        $audit = new SpyAuditLogPort();

        $policy = new TransactionEntryPolicy($actors, $capabilities, $audit);

        $result = $policy->decide('admin-1');

        self::assertTrue($result->isFailure());
        self::assertSame(['ADMIN_TRANSACTION_CAPABILITY_DISABLED'], $result->errors()['capability']);
        self::assertCount(0, $audit->records);
    }

    public function test_admin_with_active_capability_is_allowed_and_audited(): void
    {
        $actors = new InMemoryActorAccessReaderPort([
            'admin-1' => new ActorAccess('admin-1', Role::admin()),
        ]);
        $capabilities = new InMemoryAdminTransactionCapabilityStatePort([
            'admin-1' => AdminTransactionCapabilityState::active('admin-1'),
        ]);
        $audit = new SpyAuditLogPort();

        $policy = new TransactionEntryPolicy($actors, $capabilities, $audit);

        $result = $policy->decide('admin-1', ['note_id' => 'NOTE-001']);

        self::assertTrue($result->isSuccess());
        self::assertSame(true, $result->data()['allowed']);
        self::assertSame('admin_transaction_entry', $result->data()['capability']);
        self::assertSame('admin_capability_allow', $result->data()['score']);

        self::assertCount(1, $audit->records);
        self::assertSame('admin_transaction_capability_used', $audit->records[0]['event']);
        self::assertSame('admin-1', $audit->records[0]['context']['actor_id']);
        self::assertSame(['note_id' => 'NOTE-001'], $audit->records[0]['context']['context']);
    }

    public function test_unknown_actor_is_rejected(): void
    {
        $actors = new InMemoryActorAccessReaderPort();
        $capabilities = new InMemoryAdminTransactionCapabilityStatePort();
        $audit = new SpyAuditLogPort();

        $policy = new TransactionEntryPolicy($actors, $capabilities, $audit);

        $result = $policy->decide('unknown');

        self::assertTrue($result->isFailure());
        self::assertSame(['ACTOR_NOT_FOUND'], $result->errors()['actor']);
        self::assertCount(0, $audit->records);
    }
}

final class InMemoryActorAccessReaderPort implements ActorAccessReaderPort
{
    /**
     * @param array<string, ActorAccess> $items
     */
    public function __construct(
        private array $items = [],
    ) {
    }

    public function findByActorId(string $actorId): ?ActorAccess
    {
        return $this->items[$actorId] ?? null;
    }
}

final class InMemoryAdminTransactionCapabilityStatePort implements AdminTransactionCapabilityStatePort
{
    /**
     * @param array<string, AdminTransactionCapabilityState> $items
     */
    public function __construct(
        private array $items = [],
    ) {
    }

    public function getByActorId(string $actorId): AdminTransactionCapabilityState
    {
        return $this->items[$actorId] ?? AdminTransactionCapabilityState::inactive($actorId);
    }

    public function activate(string $actorId): void
    {
        $this->items[$actorId] = AdminTransactionCapabilityState::active($actorId);
    }

    public function deactivate(string $actorId): void
    {
        $this->items[$actorId] = AdminTransactionCapabilityState::inactive($actorId);
    }
}

final class SpyAuditLogPort implements AuditLogPort
{
    /**
     * @var array<int, array{event:string, context:array<string, mixed>}>
     */
    public array $records = [];

    public function record(string $event, array $context = []): void
    {
        $this->records[] = [
            'event' => $event,
            'context' => $context,
        ];
    }
}
