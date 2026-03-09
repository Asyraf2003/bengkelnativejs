<?php

declare(strict_types=1);

namespace Tests\Unit\Application\IdentityAccess\UseCases;

use App\Application\IdentityAccess\UseCases\DisableAdminTransactionCapabilityHandler;
use App\Core\IdentityAccess\Actor\ActorAccess;
use App\Core\IdentityAccess\Capability\AdminTransactionCapabilityState;
use App\Core\IdentityAccess\Role\Role;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use App\Ports\Out\IdentityAccess\AdminTransactionCapabilityStatePort;
use PHPUnit\Framework\TestCase;

final class DisableAdminTransactionCapabilityHandlerTest extends TestCase
{
    public function test_disable_capability_for_admin_succeeds_and_is_audited(): void
    {
        $actors = new InMemoryActorAccessReaderPort([
            'admin-1' => new ActorAccess('admin-1', Role::admin()),
        ]);
        $capabilities = new InMemoryAdminTransactionCapabilityStatePort([
            'admin-1' => AdminTransactionCapabilityState::active('admin-1'),
        ]);
        $audit = new SpyAuditLogPort();

        $handler = new DisableAdminTransactionCapabilityHandler($actors, $capabilities, $audit);

        $result = $handler->handle('admin-1', 'owner-1');

        self::assertTrue($result->isSuccess());
        self::assertSame('inactive', $result->data()['status']);
        self::assertTrue($capabilities->getByActorId('admin-1')->isInactive());

        self::assertCount(1, $audit->records);
        self::assertSame('admin_transaction_capability_disabled', $audit->records[0]['event']);
    }

    public function test_disable_capability_fails_for_non_admin(): void
    {
        $actors = new InMemoryActorAccessReaderPort([
            'kasir-1' => new ActorAccess('kasir-1', Role::kasir()),
        ]);
        $capabilities = new InMemoryAdminTransactionCapabilityStatePort();
        $audit = new SpyAuditLogPort();

        $handler = new DisableAdminTransactionCapabilityHandler($actors, $capabilities, $audit);

        $result = $handler->handle('kasir-1', 'owner-1');

        self::assertTrue($result->isFailure());
        self::assertSame(['ADMIN_ONLY_CAPABILITY'], $result->errors()['role']);
        self::assertCount(0, $audit->records);
    }

    public function test_disable_capability_fails_for_unknown_actor(): void
    {
        $actors = new InMemoryActorAccessReaderPort();
        $capabilities = new InMemoryAdminTransactionCapabilityStatePort();
        $audit = new SpyAuditLogPort();

        $handler = new DisableAdminTransactionCapabilityHandler($actors, $capabilities, $audit);

        $result = $handler->handle('unknown', 'owner-1');

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
