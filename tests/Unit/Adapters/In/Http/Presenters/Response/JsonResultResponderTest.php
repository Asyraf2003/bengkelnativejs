<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters\In\Http\Presenters\Response;

use App\Adapters\In\Http\Presenters\Response\JsonResultResponder;
use App\Application\Shared\DTO\Result;
use PHPUnit\Framework\TestCase;

final class JsonResultResponderTest extends TestCase
{
    public function test_success_response_uses_consistent_envelope(): void
    {
        $responder = new JsonResultResponder();

        $response = $responder->success(
            Result::success(['allowed' => true], 'OK'),
            200
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            json_encode([
                'success' => true,
                'data' => ['allowed' => true],
                'message' => 'OK',
                'errors' => [],
            ]),
            $response->getContent()
        );
    }

    public function test_failure_response_uses_consistent_envelope(): void
    {
        $responder = new JsonResultResponder();

        $response = $responder->failure(
            Result::failure('Forbidden', ['role' => ['AUTH_FORBIDDEN']]),
            403
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertSame(
            json_encode([
                'success' => false,
                'data' => null,
                'message' => 'Forbidden',
                'errors' => ['role' => ['AUTH_FORBIDDEN']],
            ]),
            $response->getContent()
        );
    }
}
