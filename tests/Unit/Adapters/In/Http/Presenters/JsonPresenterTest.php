<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\In\Http\Presenters;

use App\Adapters\In\Http\Presenters\JsonPresenter;
use App\Adapters\In\Http\Presenters\Response\JsonResultResponder;
use App\Application\Shared\DTO\Result;
use PHPUnit\Framework\TestCase;

final class JsonPresenterTest extends TestCase
{
    public function test_success_delegates_to_result_responder(): void
    {
        $presenter = new JsonPresenter(new JsonResultResponder());

        $response = $presenter->success(
            Result::success(['actor_id' => 'admin-1'], 'OK'),
            200
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('"success":true', (string) $response->getContent());
    }

    public function test_failure_delegates_to_result_responder(): void
    {
        $presenter = new JsonPresenter(new JsonResultResponder());

        $response = $presenter->failure(
            Result::failure('Denied', ['capability' => ['ADMIN_TRANSACTION_CAPABILITY_DISABLED']]),
            403
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('"success":false', (string) $response->getContent());
    }
}
