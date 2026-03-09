<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\In\Http\Middleware\IdentityAccess;

use App\Adapters\In\Http\Middleware\IdentityAccess\EnsureTransactionEntryAllowed;
use App\Adapters\In\Http\Presenters\Response\JsonResultResponder;
use App\Application\IdentityAccess\Policies\TransactionEntryPolicy;
use App\Application\Shared\DTO\Result;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTransactionEntryAllowedTest extends TestCase
{
    public function test_unauthenticated_request_returns_401(): void
    {
        $middleware = new EnsureTransactionEntryAllowed(
            new StubTransactionEntryPolicy(Result::success()),
            new JsonResultResponder(),
        );

        $request = Request::create('/transactions', 'POST');

        $response = $middleware->handle(
            $request,
            static fn (): Response => new Response('OK', 200)
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString('UNAUTHENTICATED', (string) $response->getContent());
    }

    public function test_denied_decision_returns_403(): void
    {
        $middleware = new EnsureTransactionEntryAllowed(
            new StubTransactionEntryPolicy(
                Result::failure(
                    'Forbidden',
                    ['capability' => ['ADMIN_TRANSACTION_CAPABILITY_DISABLED']]
                )
            ),
            new JsonResultResponder(),
        );

        $request = Request::create('/transactions', 'POST');
        $request->setUserResolver(static fn () => new FakeAuthUser('admin-1'));

        $response = $middleware->handle(
            $request,
            static fn (): Response => new Response('OK', 200)
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('ADMIN_TRANSACTION_CAPABILITY_DISABLED', (string) $response->getContent());
    }

    public function test_allowed_decision_passes_to_next_middleware(): void
    {
        $middleware = new EnsureTransactionEntryAllowed(
            new StubTransactionEntryPolicy(Result::success(['allowed' => true], 'OK')),
            new JsonResultResponder(),
        );

        $request = Request::create('/transactions', 'POST');
        $request->setUserResolver(static fn () => new FakeAuthUser('kasir-1'));

        $response = $middleware->handle(
            $request,
            static fn (): Response => new Response('OK', 200)
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}

final class StubTransactionEntryPolicy extends TransactionEntryPolicy
{
    public function __construct(
        private Result $result,
    ) {
    }

    public function decide(string $actorId, array $context = []): Result
    {
        return $this->result;
    }
}

final class FakeAuthUser
{
    public function __construct(
        private readonly string $id,
    ) {
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }
}
